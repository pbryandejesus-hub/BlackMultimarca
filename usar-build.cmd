@echo off
rem Hace que la web deje de depender de "npm run dev" (Vite).
rem Borra public/hot, el archivo que le indica a Laravel que pida el CSS y el JS a Vite,
rem y compila los archivos en public/build para que Laravel use esos.
rem Para volver a trabajar con recarga en vivo, alcanza con correr "npm run dev".

cd /d "%~dp0"

if exist public\hot (
    del /f /q public\hot
    echo Se borro public\hot.
) else (
    echo public\hot no existe, nada que borrar.
)

echo Compilando CSS y JS...
call npm run build
if errorlevel 1 (
    echo.
    echo La compilacion fallo, revisa el error de arriba.
    pause
    exit /b 1
)

echo.
echo Listo: la web usa los archivos compilados de public\build.
pause
