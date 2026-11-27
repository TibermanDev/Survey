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
