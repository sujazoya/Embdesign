document.addEventListener('DOMContentLoaded', function () {
    const gridBtn = document.getElementById('dcd-toggle-grid');
    const listBtn = document.getElementById('dcd-toggle-list');
    const wrapper = document.getElementById('dcd-category-wrapper');
    const filter = document.getElementById('dcd-parent-filter');

    function loadCategories(parentId = '') {
        wrapper.innerHTML = '<p>Loading...</p>';
        fetch(dcd_ajax_obj.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'dcd_load_categories',
                parent_id: parentId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                wrapper.innerHTML = data.data;
            } else {
                wrapper.innerHTML = '<p>No categories found.</p>';
            }
        });
    }

    if (gridBtn && listBtn && wrapper && filter) {
        gridBtn.addEventListener('click', () => {
            wrapper.classList.remove('dcd-list-view');
            wrapper.classList.add('dcd-grid-view');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
        });

        listBtn.addEventListener('click', () => {
            wrapper.classList.remove('dcd-grid-view');
            wrapper.classList.add('dcd-list-view');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
        });

        filter.addEventListener('change', () => {
            loadCategories(filter.value);
        });

        loadCategories(); // Load initial
    }
});
