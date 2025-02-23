document.addEventListener('DOMContentLoaded', function () {
    // Select all search input fields inside the template
    const searchInputs = document.querySelectorAll('.input-group input');

    searchInputs.forEach(function (searchInput) {
        // Find the closest table related to this search input
        const table = searchInput.closest('.details').querySelector('table tbody');

        if (!table) {
            console.error("Table body not found for search input:", searchInput);
            return;
        }

        // Function to filter table rows
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.toLowerCase().trim(); // Get search text
            const tableRows = table.querySelectorAll('tr'); // Get all rows inside tbody

            tableRows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase(); // Get text of the row
                row.style.display = rowText.includes(query) ? '' : 'none'; // Show/hide row
            });
        });
    });

    // Function to export tables to PDF
    function exportTableToPDF(tableId, filename) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Convert HTML table to PDF with autoTable
        doc.text(filename, 10, 10); // Add title to PDF
        doc.autoTable({ html: tableId, startY: 20 }); // Convert table to PDF with autoTable
        doc.save(filename + ".pdf");
    }

    // Function to export tables to Excel
    function exportTableToExcel(tableId, filename) {
        const table = document.querySelector(tableId);
        const workbook = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(workbook, filename + ".xlsx");
    }

    // Attach event listeners to buttons for 'history' section
    document.getElementById('toPDF-history').addEventListener('click', function() {
        exportTableToPDF('#history table', 'History'); // Export the history table to PDF
    });

    document.getElementById('toEXCEL-history').addEventListener('click', function() {
        exportTableToExcel('#history table', 'History'); // Export the historytable to Excel
    });

});
