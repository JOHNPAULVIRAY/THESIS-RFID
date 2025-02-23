document.addEventListener('DOMContentLoaded', function () {
    // Get all search input fields
    const searchInputs = document.querySelectorAll('.input-group input');

    // Function to handle table search
    function handleSearch(input, table) {
        const tableRows = table.querySelectorAll('tbody tr'); // Get rows of the specific table
        
        input.addEventListener('input', function () {
            const query = input.value.toLowerCase(); // Get the search query and convert it to lowercase
            tableRows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase(); // Get text content of each row
                if (rowText.includes(query)) {
                    row.style.display = ''; // Show row if it matches the search query
                } else {
                    row.style.display = 'none'; // Hide row if it doesn't match the search query
                }
            });
        });
    }

    // Initialize search functionality for each input field and corresponding table
    const tables = document.querySelectorAll('.table__body table'); // All tables in the page
    tables.forEach(function(table, index) {
        const searchInput = searchInputs[index]; // Get the corresponding search input for each table
        if (searchInput) {
            handleSearch(searchInput, table);
        }
    });
});
