const addBookBtn = document.querySelector('.add-book-btn');
const addBookModal = document.getElementById('addBookModal');
const modalClose = document.querySelector('.modal-close');
const cancelBtn = document.getElementById('cancelBtn');
const booksContainer = document.getElementById('booksContainer');
const searchBar = document.querySelector('.manage-book-search-bar');
const filterBtn = document.getElementById('filterBtn');
const filterDropdown = document.getElementById('filterDropdown');
const searchFieldBtn = document.getElementById('searchFieldBtn');
const searchFieldMenu = document.getElementById('searchFieldMenu');

let books = [];
let editingBookId = null;
let filterMode = null; // track current filter: null, 'available'
let searchField = 'all'; // 'all', 'title', 'author', 'isbn', 'category'

console.log('add-new-book.js loaded');

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
    if (filterMode === 'available') {
        // Show only books where available >= 1 (exclude 0)
        const filtered = books.filter(book => parseInt(book.available) >= 1);
        displayBooks(filtered);
    } else if (filterMode === 'all') {
        // Show all books (including those with 0 available)
        displayBooks(books);
    } else {
        // No filter, show all books
        displayBooks(books);
    }
}

// Open modal
addBookBtn.addEventListener('click', () => {
    editingBookId = null;
    // reset modal title and submit button
    const modalTitle = addBookModal.querySelector('h2');
    const submitBtn = addBookModal.querySelector('.submit-btn');
    if (modalTitle) modalTitle.textContent = 'Add New Book';
    if (submitBtn) submitBtn.textContent = 'Add Book';
    addBookModal.style.display = 'flex';
});

// Close modal
modalClose.addEventListener('click', () => {
    addBookModal.style.display = 'none';
});

cancelBtn.addEventListener('click', () => {
    addBookModal.style.display = 'none';
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === addBookModal) {
        addBookModal.style.display = 'none';
    }
});

// Generate book HTML
function createBookCard(book) {
    return `
        <div class="book-card" data-book-id="${book.id}">
            <div class="book-card-header">
                <h3 class="book-title">${book.title}</h3>
                <div class="book-actions">
                    <button class="edit-btn" onclick="editBook(${book.id})">Edit</button>
                    <button class="delete-btn" onclick="deleteBook(${book.id})">Delete</button>
                </div>
            </div>
            <div class="book-meta">
                <div class="col">
                    <span class="lbl">Author:</span>
                    <span class="val">${book.author || ''}</span>
                </div>
                <div class="col">
                    <span class="lbl">ISBN:</span>
                    <span class="val">${book.isbn || ''}</span>
                </div>
                <div class="col">
                    <span class="lbl">Quantity:</span>
                    <span class="val">${book.quantity || 0}</span>
                </div>
                <div class="col">
                    <span class="lbl">Category:</span>
                    <span class="val">${book.category || ''}</span>
                </div>
                <div class="col">
                    <span class="lbl">Available:</span>
                    <span class="val">${book.available ?? ''}</span>
                </div>
            </div>
        </div>
    `;
}

// Display all books
function displayBooks(booksToDisplay = books) {
    booksContainer.innerHTML = '';
    if (booksToDisplay.length === 0) {
        booksContainer.innerHTML = '<p class="no-books">No books found. Add a new book to get started!</p>';
        return;
    }
    booksToDisplay.forEach(book => {
        booksContainer.innerHTML += createBookCard(book);
    });
}

// Handle form submission
document.getElementById('addBookForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const title = document.getElementById('bookTitle').value;
    const author = document.getElementById('bookAuthor').value;
    const isbn = document.getElementById('bookISBN').value;
    const quantity = document.getElementById('bookQuantity').value;
    const category = document.getElementById('bookCategory').value;
    const availableInput = document.getElementById('bookAvailable');
    const availableVal = availableInput ? availableInput.value : '';
    
    // Parse quantity and available; ensure 0 is preserved
    const quantityNum = (quantity !== '' && quantity !== null) ? parseInt(quantity, 10) : 0;
    const availableNum = (availableVal !== '' && availableVal !== null) ? parseInt(availableVal, 10) : quantityNum;
    
    const payload = {
        title: title,
        author: author,
        isbn: isbn,
        quantity: quantityNum,
        category: category,
        available: availableNum
    };

    if (editingBookId) {
        // update existing book on server
        payload.id = editingBookId;
        fetch('update_book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                loadBooks();
            } else {
                alert('Error updating book: ' + (data.error || 'Unknown'));
            }
        }).catch(err => {
            console.error(err);
            alert('Error updating book');
        }).finally(() => {
            document.getElementById('addBookForm').reset();
            editingBookId = null;
            addBookModal.style.display = 'none';
        });
    } else {
        // add new book to server
        fetch('add_book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                loadBooks();
            } else {
                alert('Error adding book: ' + (data.error || 'Unknown'));
            }
        }).catch(err => {
            console.error(err);
            alert('Error adding book');
        }).finally(() => {
            document.getElementById('addBookForm').reset();
            editingBookId = null;
            addBookModal.style.display = 'none';
        });
    }
});

// Delete book
function deleteBook(bookId) {
    if (confirm('Are you sure you want to delete this book?')) {
        fetch('delete_book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: bookId })
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                loadBooks();
            } else {
                alert('Error deleting book: ' + (data.error || 'Unknown'));
            }
        }).catch(err => {
            console.error(err);
            alert('Error deleting book');
        });
    }
}

// Edit book - populate modal with book data
function editBook(bookId) {
    const book = books.find(b => b.id === bookId);
    if (!book) return;
    editingBookId = bookId;
    document.getElementById('bookTitle').value = book.title || '';
    document.getElementById('bookAuthor').value = book.author || '';
    document.getElementById('bookISBN').value = book.isbn || '';
    document.getElementById('bookQuantity').value = book.quantity || '';
    document.getElementById('bookCategory').value = book.category || '';
    const availEl = document.getElementById('bookAvailable');
    if (availEl) availEl.value = (typeof book.available !== 'undefined' ? book.available : book.quantity) || '';

    const modalTitle = addBookModal.querySelector('h2');
    const submitBtn = addBookModal.querySelector('.submit-btn');
    if (modalTitle) modalTitle.textContent = 'Edit Book';
    if (submitBtn) submitBtn.textContent = 'Save Changes';

    addBookModal.style.display = 'flex';
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
async function loadBooks() {
    console.log('loadBooks() starting: pinging server');
    try {
        const ping = await fetch('get_books_ping.php');
        console.log('ping status', ping.status, ping.ok);
    } catch (err) {
        console.error('Ping failed', err);
    }
    try {
        const res = await fetch('get_books.php');
        if (!res.ok) {
            const txt = await res.text();
            console.error('get_books.php returned', res.status, txt);
            books = [];
            booksContainer.innerHTML = '<p class="no-books">Error loading books. See console for details.</p>';
            return;
        }
        const data = await res.json().catch(async parseErr => {
            const txt = await res.text().catch(() => '<no body>');
            console.error('Failed to parse JSON from get_books.php:', parseErr, 'body:', txt);
            return null;
        });
        console.log('get_books response', data);
        if (data && data.success && Array.isArray(data.books)) {
            books = data.books.map(b => ({
                id: b.id || Date.now(),
                title: b.title || 'Untitled',
                author: b.author || '',
                isbn: b.isbn || '',
                quantity: b.quantity || 0,
                category: b.category || '',
                available: b.available ?? b.quantity ?? 0
            }));
        } else {
            books = [];
        }
    } catch (err) {
        console.error('Error loading books', err);
        booksContainer.innerHTML = '<p class="no-books">Error loading books. See console for details.</p>';
    }
    displayBooks();
}

loadBooks();