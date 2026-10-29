/**
 * Interacciones de la tienda: menú móvil, galería, visor 3D, selector de variantes,
 * cantidades, copiar datos bancarios y filas de variantes en el panel de administración.
 */

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

function initToggles() {
    $$('[data-toggle]').forEach((button) => {
        const target = document.getElementById(button.dataset.toggle);
        if (!target) return;

        button.addEventListener('click', () => {
            const isOpen = target.classList.toggle('hidden') === false;
            button.setAttribute('aria-expanded', String(isOpen));
        });
    });
}

/**
 * Galería de la ficha de producto: miniaturas, lupa al pasar el mouse y fotos en pantalla completa
 * (flechas, teclado y deslizar con el dedo).
 */
function initGallery() {
    const main = $('[data-gallery-main]');
    if (!main) return;

    const images = JSON.parse(main.dataset.galleryImages || '[]');
    const thumbs = $$('[data-gallery-thumb]');
    let current = 0;

    const show = (index) => {
        current = index;
        main.src = images[index];
        thumbs.forEach((thumb, thumbIndex) => thumb.setAttribute('aria-pressed', String(thumbIndex === index)));
    };

    thumbs.forEach((thumb, index) => thumb.addEventListener('click', () => show(index)));

    const opener = $('[data-gallery-open]');

    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        opener.addEventListener('mousemove', (event) => {
            const box = opener.getBoundingClientRect();
            main.style.transformOrigin = `${((event.clientX - box.left) / box.width) * 100}% ${((event.clientY - box.top) / box.height) * 100}%`;
            main.style.transform = 'scale(2)';
        });
        opener.addEventListener('mouseleave', () => (main.style.transform = ''));
    }

    const lightbox = $('[data-lightbox]');
    if (!lightbox) return;

    const lightboxImage = $('[data-lightbox-image]', lightbox);
    const counter = $('[data-lightbox-counter]', lightbox);

    const showInLightbox = (index) => {
        show((index + images.length) % images.length);
        lightboxImage.src = images[current];
        counter.textContent = `${current + 1} / ${images.length}`;
    };

    opener.addEventListener('click', () => {
        main.style.transform = '';
        showInLightbox(current);
        lightbox.showModal();
    });

    $('[data-lightbox-close]', lightbox).addEventListener('click', () => lightbox.close());
    $('[data-lightbox-prev]', lightbox)?.addEventListener('click', () => showInLightbox(current - 1));
    $('[data-lightbox-next]', lightbox)?.addEventListener('click', () => showInLightbox(current + 1));

    lightbox.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') showInLightbox(current - 1);
        if (event.key === 'ArrowRight') showInLightbox(current + 1);
    });

    let touchStartX = null;
    lightboxImage.addEventListener('touchstart', (event) => (touchStartX = event.touches[0].clientX), { passive: true });
    lightboxImage.addEventListener('touchend', (event) => {
        if (touchStartX === null || images.length < 2) return;

        const distance = event.changedTouches[0].clientX - touchStartX;
        if (Math.abs(distance) > 50) showInLightbox(current + (distance < 0 ? 1 : -1));
        touchStartX = null;
    });
}

/**
 * Visor 3D de la ficha de producto. El componente <model-viewer> de Google pesa bastante,
 * así que se descarga recién la primera vez que el cliente abre la vista 3D.
 */
function initModelViewer() {
    const dialog = $('[data-model-dialog]');
    if (!dialog) return;

    const viewer = $('model-viewer', dialog);
    const progress = $('[data-model-progress]', dialog);
    const error = $('[data-model-error]', dialog);
    let loading = null;

    $$('[data-model-open]').forEach((button) =>
        button.addEventListener('click', () => {
            dialog.showModal();
            loading ??= import('@google/model-viewer').catch(() => {
                loading = null;
                error.hidden = false;
                progress.hidden = true;
            });
        }),
    );

    $('[data-model-close]', dialog).addEventListener('click', () => dialog.close());

    viewer.addEventListener('progress', (event) => {
        const total = event.detail.totalProgress;
        progress.firstElementChild.style.width = `${total * 100}%`;
        progress.hidden = total >= 1;
    });

    viewer.addEventListener('error', () => {
        error.hidden = false;
        progress.hidden = true;
    });
}

function initQuantityInputs() {
    $$('[data-quantity]').forEach((wrapper) => {
        const input = $('input', wrapper);
        const clamp = (value) => {
            const min = Number(input.min || 1);
            const max = Number(input.max || 99);
            return Math.min(max, Math.max(min, value));
        };

        $('[data-quantity-dec]', wrapper)?.addEventListener('click', () => {
            input.value = clamp(Number(input.value) - 1);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        $('[data-quantity-inc]', wrapper)?.addEventListener('click', () => {
            input.value = clamp(Number(input.value) + 1);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    $$('[data-autosubmit]').forEach((input) => {
        input.addEventListener('change', () => input.form?.requestSubmit());
    });
}

/**
 * Selector de color y talle en la ficha de producto. Las variantes vienen como JSON en data-variants.
 */
function initVariantPicker() {
    const picker = $('[data-variant-picker]');
    if (!picker) return;

    const variants = JSON.parse(picker.dataset.variants);
    const hiddenInput = $('[data-variant-input]', picker);
    const fallbackSelect = $('[data-variant-fallback]', picker);
    const stockMessage = $('[data-stock-message]', picker);
    const colorName = $('[data-color-name]', picker);
    const submit = $('[data-add-to-cart]');
    const quantityInput = $('[data-quantity] input');

    const colorButtons = $$('[data-color]', picker);
    const sizeButtons = $$('[data-size]', picker);
    const hasColors = colorButtons.length > 0;
    const hasSizes = sizeButtons.length > 0;

    fallbackSelect?.remove();
    $$('[data-variant-buttons]', picker).forEach((element) => (element.hidden = false));

    const firstAvailable = variants.find((variant) => variant.stock > 0) ?? variants[0];
    const state = {
        color: hasColors ? firstAvailable?.color ?? null : null,
        size: null,
    };

    const findVariant = () =>
        variants.find(
            (variant) =>
                (!hasColors || variant.color === state.color) && (!hasSizes || variant.size === state.size),
        );

    const render = () => {
        colorButtons.forEach((button) => {
            const selected = button.dataset.color === state.color;
            button.setAttribute('aria-pressed', String(selected));
            button.classList.toggle('ring-2', selected);
        });

        sizeButtons.forEach((button) => {
            const variant = variants.find(
                (item) => item.size === button.dataset.size && (!hasColors || item.color === state.color),
            );
            const available = Boolean(variant && variant.stock > 0);
            const selected = button.dataset.size === state.size;

            button.disabled = !available;
            button.setAttribute('aria-pressed', String(selected));
            button.classList.toggle('bg-ink', selected);
            button.classList.toggle('text-gold-light', selected);
            button.classList.toggle('border-ink', selected);
            button.classList.toggle('line-through', !available);
        });

        if (colorName) colorName.textContent = state.color ?? '';

        const variant = findVariant();
        const ready = variant && variant.stock > 0;
        hiddenInput.value = ready ? variant.id : '';
        submit.disabled = !ready;

        if (quantityInput && ready) {
            quantityInput.max = variant.stock;
            if (Number(quantityInput.value) > variant.stock) quantityInput.value = variant.stock;
        }

        if (!variant && hasSizes && !state.size) {
            stockMessage.textContent = 'Elegí un talle.';
        } else if (!ready) {
            stockMessage.textContent = 'Sin stock en esta combinación.';
        } else if (variant.stock <= 2) {
            stockMessage.textContent = `¡Quedan solo ${variant.stock}!`;
        } else {
            stockMessage.textContent = 'Stock disponible';
        }
    };

    colorButtons.forEach((button) =>
        button.addEventListener('click', () => {
            state.color = button.dataset.color;
            const current = findVariant();
            if (hasSizes && (!current || current.stock < 1)) state.size = null;
            render();
        }),
    );

    sizeButtons.forEach((button) =>
        button.addEventListener('click', () => {
            state.size = button.dataset.size;
            render();
        }),
    );

    render();
}

function initCopyButtons() {
    $$('[data-copy]').forEach((button) => {
        const original = button.textContent;
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                button.textContent = 'Copiado ✓';
            } catch {
                button.textContent = 'No se pudo copiar';
            }
            setTimeout(() => (button.textContent = original), 2000);
        });
    });
}

/**
 * En el checkout: muestra los campos de dirección solo para envío y actualiza el total.
 */
function initDeliveryOptions() {
    const options = $$('input[name="delivery_method"]');
    if (!options.length) return;

    const addressFields = $('[data-shipping-fields]');
    const update = () => {
        const selected = options.find((option) => option.checked)?.value;
        if (addressFields) addressFields.hidden = selected !== 'envio';
        $$('[data-summary-for]').forEach((element) => (element.hidden = element.dataset.summaryFor !== selected));
    };

    options.forEach((option) => option.addEventListener('change', update));
    update();
}

/**
 * Panel de administración: agregar y quitar filas de variantes.
 */
function initVariantRows() {
    const container = $('[data-variant-rows]');
    const template = $('#variant-row-template');
    if (!container || !template) return;

    let nextIndex = Number(container.dataset.nextIndex || container.children.length);

    $('[data-add-variant]')?.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        container.insertAdjacentHTML('beforeend', html);
    });

    container.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-variant]');
        if (!button) return;

        if (container.children.length <= 1) {
            alert('El producto necesita al menos una variante.');
            return;
        }

        button.closest('[data-variant-row]').remove();
    });
}

/**
 * Panel de administración: al llegar desde "Poco stock" (#variante-ID) baja suave hasta esa variante,
 * la ilumina y enfoca su stock. La fila no tiene id para que el navegador no salte de golpe.
 */
function initTargetedVariant() {
    const variantId = location.hash.match(/^#variante-(\d+)$/)?.[1];
    const row = variantId && $(`[data-variant-id="${variantId}"]`);
    if (!row) return;

    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const stockInput = $('[data-variant-stock]', row);

    setTimeout(() => {
        row.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        setTimeout(
            () => {
                row.classList.add('variant-highlight');
                stockInput?.focus({ preventScroll: true });
                stockInput?.select();
            },
            reduceMotion ? 0 : 600,
        );
    }, 300);
}

/**
 * Gráficos del panel de administración. Cada <canvas data-chart> trae su tipo, etiquetas y valores en JSON;
 * Chart.js se descarga solo en las páginas que tienen gráficos.
 */
async function initCharts() {
    const canvases = $$('canvas[data-chart]');
    if (!canvases.length) return;

    const { default: Chart } = await import('chart.js/auto');

    const palette = ['#c9a96e', '#7a5a2e', '#0d0d0d', '#e8d5a8', '#3d3830', '#cdbfa6'];
    const guaranies = new Intl.NumberFormat('es-PY');
    const formatMoney = (value) => `₲ ${guaranies.format(value)}`;

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#5e5850';
    Chart.defaults.borderColor = '#e3d9c6';

    canvases.forEach((canvas) => {
        const { type, labels, values, money = false, horizontal = false, unit = '' } = JSON.parse(canvas.dataset.chart);
        const formatValue = (value) => (money ? formatMoney(value) : `${guaranies.format(value)} ${unit}`.trim());
        const isDoughnut = type === 'doughnut';

        new Chart(canvas, {
            type,
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: isDoughnut ? palette : type === 'line' ? 'rgba(201, 169, 110, 0.18)' : '#c9a96e',
                        borderColor: isDoughnut ? '#ffffff' : '#7a5a2e',
                        borderWidth: type === 'bar' ? 0 : 2,
                        fill: type === 'line',
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        hoverBackgroundColor: isDoughnut ? undefined : '#7a5a2e',
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: horizontal ? 'y' : 'x',
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: isDoughnut, position: 'bottom' },
                    tooltip: {
                        backgroundColor: '#0d0d0d',
                        titleColor: '#e8d5a8',
                        callbacks: { label: (context) => ` ${formatValue(context.raw)}` },
                    },
                },
                scales: isDoughnut
                    ? {}
                    : {
                          [horizontal ? 'x' : 'y']: {
                              beginAtZero: true,
                              ticks: { callback: (value) => (money ? formatMoney(value) : value), precision: 0 },
                          },
                          [horizontal ? 'y' : 'x']: { grid: { display: false } },
                      },
            },
        });
    });
}

function initConfirmations() {
    $$('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initToggles();
    initGallery();
    initModelViewer();
    initQuantityInputs();
    initVariantPicker();
    initCopyButtons();
    initDeliveryOptions();
    initVariantRows();
    initTargetedVariant();
    initConfirmations();
    initCharts();
});
