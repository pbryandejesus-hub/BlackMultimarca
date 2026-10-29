from __future__ import annotations

import json
import re
import subprocess
import threading
import time
import uuid
from datetime import datetime
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.error import URLError
from urllib.request import urlopen


PROJECT = Path(__file__).resolve().parents[2]
ROOT = Path(__file__).parent
TEST_HISTORY = ROOT / 'latest-test-run.json'
LOCK = threading.Lock()
TEST_STATE: dict[str, object] = {
    'state': 'idle',
    'ok': None,
    'output': '',
    'finished_at': '',
    'mode': '',
    'summary': '',
}
try:
    saved_test_state = json.loads(TEST_HISTORY.read_text())
    if isinstance(saved_test_state, dict) and saved_test_state.get('state') == 'done':
        TEST_STATE.update(saved_test_state)
except (OSError, json.JSONDecodeError):
    pass

ANSI_ESCAPE = re.compile(r'\x1b\[[0-?]*[ -/]*[@-~]')


def update_test_state(**changes: object) -> None:
    with LOCK:
        TEST_STATE.update(changes)


def run_tests(mode: str) -> None:
    db_container = ''
    env_args: list[str]
    if mode == 'mysql':
        db_container = f'black-audit-mysql-{uuid.uuid4().hex[:8]}'
        network_result = subprocess.run(
            ['docker', 'inspect', 'programacion-iv-app', '--format', '{{range $network, $_ := .NetworkSettings.Networks}}{{$network}}{{end}}'],
            capture_output=True, text=True, timeout=5, check=False,
        )
        network = network_result.stdout.strip()
        if network_result.returncode != 0 or not network:
            update_test_state(state='done', ok=False, output='No se encontró la red del contenedor de la web.', finished_at=datetime.now().astimezone().strftime('%d/%m/%Y %H:%M:%S'))
            return

        update_test_state(output='Creando una base MySQL temporal aislada…')
        password = 'local-audit-only'
        start_result = subprocess.run(
            [
                'docker', 'run', '--detach', '--rm', '--name', db_container,
                '--network', network,
                '--label', 'com.blackmultimarca.local-audit=true',
                '--health-cmd', f'mysqladmin ping -h 127.0.0.1 -uroot -p{password}',
                '--health-interval', '2s', '--health-timeout', '2s', '--health-retries', '40',
                '--env', 'MYSQL_DATABASE=black_multimarca_test',
                '--env', 'MYSQL_USER=audit', '--env', f'MYSQL_PASSWORD={password}',
                '--env', f'MYSQL_ROOT_PASSWORD={password}', 'mysql:8.4',
            ], capture_output=True, text=True, timeout=20, check=False,
        )
        if start_result.returncode != 0:
            update_test_state(state='done', ok=False, output=f'No se pudo iniciar MySQL temporal.\n{start_result.stderr[-4000:]}', finished_at=datetime.now().astimezone().strftime('%d/%m/%Y %H:%M:%S'))
            return

        update_test_state(output='Esperando que MySQL temporal esté listo…')
        deadline = time.monotonic() + 90
        healthy = False
        while time.monotonic() < deadline:
            inspect = subprocess.run(
                ['docker', 'inspect', db_container, '--format', '{{.State.Health.Status}}'],
                capture_output=True, text=True, timeout=5, check=False,
            )
            if inspect.stdout.strip() == 'healthy':
                healthy = True
                break
            if inspect.returncode != 0:
                break
            time.sleep(2)
        if not healthy:
            logs = subprocess.run(['docker', 'logs', '--tail=30', db_container], capture_output=True, text=True, timeout=8, check=False)
            subprocess.run(['docker', 'rm', '--force', db_container], capture_output=True, timeout=10, check=False)
            update_test_state(state='done', ok=False, output=f'MySQL temporal no quedó listo.\n{logs.stdout[-5000:]}{logs.stderr[-2000:]}', finished_at=datetime.now().astimezone().strftime('%d/%m/%Y %H:%M:%S'))
            return

        env_args = [
            'APP_ENV=testing', 'APP_DEBUG=false', 'APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            f'DB_CONNECTION=mysql', f'DB_HOST={db_container}', 'DB_PORT=3306',
            'DB_DATABASE=black_multimarca_test', 'DB_USERNAME=audit', f'DB_PASSWORD={password}', 'DB_URL=',
        ]
    else:
        env_args = [
            'APP_ENV=testing', 'APP_DEBUG=false', 'APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'DB_CONNECTION=sqlite', 'DB_DATABASE=:memory:', 'DB_URL=',
        ]

    env_args.extend([
        'CACHE_STORE=array', 'SESSION_DRIVER=array', 'QUEUE_CONNECTION=sync',
        'MAIL_MAILER=array', 'LOG_CHANNEL=stderr', 'BCRYPT_ROUNDS=4',
    ])
    command = [
        'docker', 'compose', 'exec', '-T', 'app', 'env',
        *env_args, 'vendor/bin/phpunit', '--testdox', '--no-coverage',
    ]
    try:
        update_test_state(output='Suite MySQL completa en ejecución…' if mode == 'mysql' else 'Suite SQLite aislada en ejecución…')
        result = subprocess.run(
            command,
            cwd=PROJECT,
            capture_output=True,
            text=True,
            timeout=300,
            check=False,
        )
        output = (result.stdout + result.stderr)[-24000:]
        ok = result.returncode == 0
    except subprocess.TimeoutExpired as error:
        output = f'La suite superó el límite de 5 minutos.\n{error.stdout or ""}{error.stderr or ""}'[-24000:]
        ok = False
    except Exception as error:
        output = f'No se pudo iniciar la suite: {error}'
        ok = False

    finally:
        if db_container:
            update_test_state(output='Suite terminada. Limpiando la base temporal…')
            subprocess.run(['docker', 'rm', '--force', db_container], capture_output=True, timeout=15, check=False)

    finished_at = datetime.now().astimezone().strftime('%d/%m/%Y %H:%M:%S')
    clean_output = ANSI_ESCAPE.sub('', output).strip() or 'La suite terminó sin salida.'
    summary = next((line.strip() for line in reversed(clean_output.splitlines()) if line.strip().startswith(('Tests:', 'OK (', 'FAILURES!', 'ERRORS!'))), '')
    with LOCK:
        TEST_STATE.update(state='done', ok=ok, output=clean_output, finished_at=finished_at, mode=mode, summary=summary)
        saved_state = dict(TEST_STATE)
    temporary_history = TEST_HISTORY.with_suffix('.tmp')
    temporary_history.write_text(json.dumps(saved_state, ensure_ascii=False))
    temporary_history.replace(TEST_HISTORY)


class Handler(BaseHTTPRequestHandler):
    server_version = 'BlackAuditLocal/1.0'

    def log_message(self, _format: str, *_args: object) -> None:
        return

    def send_json(self, payload: object, status: int = 200) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Content-Length', str(len(body)))
        self.send_header('Cache-Control', 'no-store')
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self) -> None:
        if self.path == '/':
            body = (ROOT / 'index.html').read_bytes()
            self.send_response(200)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.send_header('Content-Length', str(len(body)))
            self.send_header('Cache-Control', 'no-store')
            self.end_headers()
            self.wfile.write(body)
            return

        if self.path == '/api/status':
            self.send_json(self.status_payload())
            return

        if self.path == '/api/checks':
            self.send_json(self.checks_payload())
            return

        if self.path == '/api/logs':
            result = subprocess.run(
                ['docker', 'compose', 'logs', '--no-color', '--tail=60', 'app', 'frontend', 'scheduler'],
                cwd=PROJECT,
                capture_output=True,
                text=True,
                timeout=8,
                check=False,
            )
            logs = (result.stdout + result.stderr)[-40000:]
            self.send_json({'logs': logs.strip(), 'ok': result.returncode == 0})
            return

        if self.path == '/api/tests':
            with LOCK:
                self.send_json(dict(TEST_STATE))
            return

        self.send_json({'error': 'No encontrado'}, 404)

    def do_POST(self) -> None:
        if self.path != '/api/run-tests':
            self.send_json({'error': 'No encontrado'}, 404)
            return

        try:
            content_length = min(int(self.headers.get('Content-Length', '0')), 200)
            request = json.loads(self.rfile.read(content_length) or b'{}')
            mode = request.get('mode', 'mysql')
        except (ValueError, json.JSONDecodeError):
            self.send_json({'error': 'Solicitud inválida'}, 400)
            return
        if mode not in {'mysql', 'sqlite'}:
            self.send_json({'error': 'Modo de prueba no válido'}, 400)
            return

        with LOCK:
            if TEST_STATE['state'] == 'running':
                self.send_json({'state': 'running'})
                return
            TEST_STATE.update(state='running', ok=None, output='Preparando pruebas aisladas…', finished_at='', mode=mode, summary='')

        threading.Thread(target=run_tests, args=(mode,), daemon=True).start()
        self.send_json({'state': 'running'}, 202)

    def status_payload(self) -> dict[str, object]:
        docker_result = subprocess.run(
            ['docker', 'compose', 'ps', '--format', 'json'],
            cwd=PROJECT,
            capture_output=True,
            text=True,
            timeout=5,
            check=False,
        )
        services = []
        if docker_result.returncode == 0:
            for line in docker_result.stdout.splitlines():
                try:
                    item = json.loads(line)
                    services.append({
                        'name': item.get('Service') or item.get('Name', 'servicio'),
                        'status': item.get('Status') or item.get('State', 'desconocido'),
                    })
                except json.JSONDecodeError:
                    continue

        try:
            with urlopen('http://127.0.0.1:8000/up', timeout=2) as response:
                app_ok = response.status == 200
                app_code = response.status
        except (URLError, TimeoutError, OSError):
            app_ok = False
            app_code = 0

        return {
            'app': {'ok': app_ok, 'code': app_code, 'url': 'http://localhost:8000'},
            'docker': {'ok': docker_result.returncode == 0, 'error': docker_result.stderr.strip() if docker_result.returncode else ''},
            'services': services,
        }

    def checks_payload(self) -> dict[str, object]:
        checks: list[dict[str, object]] = []
        endpoints = [
            ('Página principal', 'http://127.0.0.1:8000/', 'La tienda responde con su página inicial.'),
            ('Salud Laravel', 'http://127.0.0.1:8000/up', 'Endpoint de disponibilidad de Laravel.'),
            ('Vite / recursos', 'http://127.0.0.1:5173/@vite/client', 'Servidor de recursos del entorno de desarrollo.'),
        ]
        for name, url, description in endpoints:
            try:
                with urlopen(url, timeout=3) as response:
                    checks.append({'name': name, 'ok': response.status == 200, 'detail': f'HTTP {response.status}', 'description': description})
            except (URLError, TimeoutError, OSError) as error:
                checks.append({'name': name, 'ok': False, 'detail': str(error.reason) if isinstance(error, URLError) else 'Sin respuesta', 'description': description})

        status = self.status_payload()
        service_map = {str(item['name']): str(item['status']) for item in status['services']}
        for key, name, description in [
            ('app', 'Contenedor Laravel', 'Servidor PHP-FPM/de desarrollo en ejecución y saludable.'),
            ('mysql', 'Base MySQL de la tienda', 'Servicio MySQL de desarrollo saludable.'),
            ('scheduler', 'Planificador', 'Proceso Laravel que ejecuta tareas programadas.'),
            ('frontend', 'Servidor de frontend', 'Vite activo para los recursos de desarrollo.'),
        ]:
            detail = service_map.get(key, 'Servicio no iniciado')
            healthy = 'healthy' in detail.lower() or (key in {'frontend', 'scheduler'} and detail.lower().startswith('up'))
            checks.append({'name': name, 'ok': healthy, 'detail': detail, 'description': description})

        return {'checks': checks, 'updated_at': datetime.now().astimezone().strftime('%H:%M:%S')}


ThreadingHTTPServer(('127.0.0.1', 8090), Handler).serve_forever()
