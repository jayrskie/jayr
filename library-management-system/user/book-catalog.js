(function(){
    const booksContainer = document.getElementById('booksContainer');
    const searchBar = document.getElementById('bookSearch');
    const filterBtn = document.getElementById('filterBtn');
    const filterDropdown = document.getElementById('filterDropdown');
    const searchFieldBtn = document.getElementById('searchFieldBtn');
    const searchFieldMenu = document.getElementById('searchFieldMenu');

    let books = [];
    let filterMode = null; // 'available', 'all', or null
    let searchField = 'all'; // 'all', 'title', 'author', 'isbn', 'category'

    function createBookCard(book){
        const avail = (typeof book.available !== 'undefined' && book.available !== null) ? parseInt(book.available) : (book.quantity ? parseInt(book.quantity) : 0);
        const availLabel = avail === 0 ? 'None' : avail;
        // only show borrow button when available >= 1
        const borrowButtonHtml = avail >= 1 ? '<button class="borrow-btn" type="button">Borrow</button>' : '';
        return `
            <div class="book-card" data-book-id="${book.id}">
                <div class="book-card-header">
                    <h3 class="book-title">${book.title || 'Untitled'}</h3>
                </div>
                <div class="book-meta">
                    <div class="col">
                        <span class="lbl">Author</span>
                        <span class="val">${book.author || ''}</span>
                    </div>
                    <div class="col">
                        <span class="lbl">ISBN</span>
                        <span class="val">${book.isbn || ''}</span>
                    </div>
                    <div class="col">
                        <span class="lbl">Quantity</span>
                        <span class="val">${book.quantity ?? 0}</span>
                    </div>
                    <div class="col">
                        <span class="lbl">Category</span>
                        <span class="val">${book.category || ''}</span>
                    </div>
                    <div class="col">
                        <span class="lbl">Available</span>
                        <span class="val">${availLabel}</span>
                    </div>
                </div>
                <!-- Expanded area (hidden until card is expanded) -->
                <div class="book-expanded" aria-hidden="true">
                    <div class="expanded-actions">
                        ${borrowButtonHtml}
                    </div>
                </div>
            </div>
        `;
    }

    function displayBooks(booksToDisplay){
        booksContainer.innerHTML = '';
        if (!Array.isArray(booksToDisplay) || booksToDisplay.length === 0) {
            booksContainer.innerHTML = '<p class="no-books">No books found. Add a new book to get started!</p>';
            return;
        }
        booksToDisplay.forEach(b => booksContainer.insertAdjacentHTML('beforeend', createBookCard(b)));
        // Attach click handlers for expand / borrow after rendering
        attachCardHandlers();
    }

    function attachCardHandlers(){
        document.querySelectorAll('.book-card').forEach(card => {
            // toggle expand on card click
            card.removeEventListener('click', card._toggleHandler);
            const toggle = (e) => {
                card.classList.toggle('expanded');
                const expandedArea = card.querySelector('.book-expanded');
                if (card.classList.contains('expanded')){
                    expandedArea && expandedArea.setAttribute('aria-hidden', 'false');
                } else {
                    expandedArea && expandedArea.setAttribute('aria-hidden', 'true');
                }
            };
            card._toggleHandler = toggle;
            card.addEventListener('click', toggle);

            // borrow button inside should not toggle card when clicked
            const borrowBtn = card.querySelector('.borrow-btn');
            if (borrowBtn){
                borrowBtn.removeEventListener('click', borrowBtn._handler);
                const handler = async (ev) => {
                    ev.stopPropagation();
                    const bookId = card.getAttribute('data-book-id');
                    const titleEl = card.querySelector('.book-title');
                    const bookTitle = titleEl ? titleEl.textContent.trim() : '';
                    // send borrow request to server
                    try {
                        const res = await fetch('add_borrow_request.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ book_id: bookId, book_title: bookTitle })
                        });
                        const data = await res.json();
                        if (data && data.success) {
                            borrowBtn.textContent = 'Requested';
                            borrowBtn.disabled = true;
                            borrowBtn.classList.add('disabled');
                        } else {
                            alert('Error requesting borrow: ' + (data.error || 'Unknown'));
                        }
                    } catch (err) {
                        console.error('Borrow request failed', err);
                        alert('Error sending borrow request');
                    }
                };
                borrowBtn._handler = handler;
                borrowBtn.addEventListener('click', handler);
            }
        });
    }

    // Filter button dropdown toggle
    filterBtn.addEventListener('click', (e) => {
        e.preventDefault();
        filterDropdown.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!filterBtn.contains(e.target) && !filterDropdown.contains(e.target)) {
            filterDropdown.classList.remove('active');
        }
    });

    // Search field dropdown toggle
    searchFieldBtn.addEventListener('click', (e) => {
        e.preventDefault();
        searchFieldMenu.classList.toggle('active');
    });

    // Close search field dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchFieldBtn.contains(e.target) && !searchFieldMenu.contains(e.target)) {
            searchFieldMenu.classList.remove('active');
        }
    });

    // Search field items
    document.querySelectorAll('.search-field-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const field = e.target.getAttribute('data-field');
            searchField = field;
            searchFieldBtn.textContent = e.target.textContent;
            searchFieldMenu.classList.remove('active');
            // Re-run search with new field if search bar has text
            if (searchBar.value) {
                searchBar.dispatchEvent(new Event('input'));
            }
        });
    });

    // Filter dropdown items
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const filter = e.target.getAttribute('data-filter');
            filterMode = filter === filterMode ? null : filter;
            filterDropdown.classList.remove('active');
            applyFilter();
        });
    });

    // Apply filter logic
    function applyFilter() {
        let filtered = books;
        
        if (filterMode === 'available') {
            filtered = books.filter(book => parseInt(book.available) >= 1);
        } else if (filterMode === 'all') {
            filtered = books;
        }
        
        displayBooks(filtered);
    }

    // Search functionality combined with filter
    searchBar.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        let filtered = books;
        
        if (searchTerm) {
            if (searchField === 'all') {
                filtered = books.filter(book =>
                    book.title.toLowerCase().includes(searchTerm) ||
                    book.author.toLowerCase().includes(searchTerm) ||
                    book.isbn.toLowerCase().includes(searchTerm) ||
                    book.category.toLowerCase().includes(searchTerm) ||
                    String(book.available).toLowerCase().includes(searchTerm)
                );
            } else if (searchField === 'title') {
                filtered = books.filter(book => book.title.toLowerCase().includes(searchTerm));
            } else if (searchField === 'author') {
                filtered = books.filter(book => book.author.toLowerCase().includes(searchTerm));
            } else if (searchField === 'isbn') {
                filtered = books.filter(book => book.isbn.toLowerCase().includes(searchTerm));
            } else if (searchField === 'category') {
                filtered = books.filter(book => book.category.toLowerCase().includes(searchTerm));
            }
        }
        
        // Apply current filter mode
        if (filterMode === 'available') {
            filtered = filtered.filter(book => parseInt(book.available) >= 1);
        }
        
        displayBooks(filtered);
    });

    // Fetch books from server on page load
    async function loadBooks(){
        try{
            const res = await fetch('../admin/get_books.php');
            if (!res.ok) {
                console.error('get_books.php returned', res.status);
                books = [];
                displayBooks([]);
                return;
            }
            const data = await res.json();
            if (data && data.success && Array.isArray(data.books)){
                books = data.books.map(b => ({
                    id: b.id || b.book_id || 0,
                    title: b.title || '',
                    author: b.author || '',
                    isbn: b.isbn || '',
                    quantity: (typeof b.quantity !== 'undefined' && b.quantity !== null) ? b.quantity : 0,
                    category: b.category || '',
                    available: (typeof b.available !== 'undefined' && b.available !== null) ? b.available : (typeof b.quantity !== 'undefined' ? b.quantity : 0)
                }));
            } else {
                books = [];
            }
        } catch (err) {
            console.error('Error loading books', err);
            books = [];
        }
        displayBooks(books);
    }

    // initial load
    loadBooks();
})();