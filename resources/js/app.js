// Pertanyaan "pilih maksimal N": nonaktifkan pilihan lain setelah N dicentang.
document.querySelectorAll('[data-max]').forEach((group) => {
    const max = Number(group.dataset.max);
    const boxes = [...group.querySelectorAll('input[type=checkbox]')];
    const update = () => {
        const full = boxes.filter((b) => b.checked).length >= max;
        boxes.forEach((b) => {
            b.disabled = full && !b.checked;
            b.closest('label')?.classList.toggle('opacity-40', b.disabled);
        });
    };
    boxes.forEach((b) => b.addEventListener('change', update));
    update();
});

// Saat dicetak, buka bagian yang dilipat tapi tetap perlu ikut tercetak.
window.addEventListener('beforeprint', () => {
    document.querySelectorAll('details[data-print-open]').forEach((d) => (d.open = true));
});

// Pilihan "Lainnya": tampilkan kolom isian saat dipilih.
document.querySelectorAll('[data-other]').forEach((box) => {
    const fieldset = box.closest('fieldset');
    const other = fieldset.querySelector('input[value="Lainnya"]');
    const input = box.querySelector('input');
    const update = (focus) => {
        box.classList.toggle('hidden', !other.checked);
        if (other.checked && focus) input.focus();
    };
    fieldset.querySelectorAll('input[type=checkbox], input[type=radio]').forEach((el) =>
        el.addEventListener('change', () => update(el === other)),
    );
    update(false);
});
