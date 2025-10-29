function searchTable() {
    const input = document.getElementById('search').value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let match = false;

        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(input)) {
                match = true;
            }
        });

        if (match) {
            row.style.display = '';
            if (row.classList.contains('panel')) {
                const previousRow = row.previousElementSibling;
                if (previousRow) {
                    previousRow.style.display = '';
                }
            }
        } else {
            row.style.display = 'none';
        }
    });
}

document.querySelectorAll('.accordion').forEach(button => {
    button.addEventListener('click', function() {
        const panel = this.closest('tr').nextElementSibling;
        panel.style.display = (panel.style.display === 'table-row') ? 'none' : 'table-row';
    });
});