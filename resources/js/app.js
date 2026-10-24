/**
 * Interacciones de la tienda sin dependencias: menú móvil, galería, selector de variantes,
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

function initGallery() {
    const main = $('[data-gallery-main]');
    if (!main) return;

    $$('[data-gallery-thumb]').forEach((thumb) => {
        thumb.addEventListener('click', () => {
            main.src = thumb.dataset.galleryThumb;
            $$('[data-gallery-thumb]').forEach((other) => other.setAttribute('aria-pressed', String(other === thumb)));
        });
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
    initQuantityInputs();
    initVariantPicker();
    initCopyButtons();
    initDeliveryOptions();
    initVariantRows();
    initConfirmations();
});
