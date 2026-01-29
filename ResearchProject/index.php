<?php 
session_start();
require_once 'connect.php';

// Function to check if user is verified
function isUserVerified() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $query = 'SELECT is_verified FROM users WHERE id = ?';
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result && $result['is_verified'];
    }
    return false;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ICT 3A Library</title>
  <meta name="description" content="ICT 3A — free resources, events, and community programs. Search the catalogue, join events, and become a member." />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .welcome {
      font-weight: 500;
      font-size: 1.1rem;
    }
    .user-menu {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-left: auto;
    }
    .user-name {
      font-weight: 500;
      font-size: 1.1rem;
    }
    .logout-btn {
      background: var(--accent);
      color: white;
      padding: 0.45rem 0.7rem;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.95rem;
      transition: background 0.3s;
    }
    .logout-btn:hover {
      background: #1a4d80;
    }

    /* Modal Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal h2 {
      margin: 0 0 1rem 0;
      color: var(--accent);
      font-size: 1.5rem;
    }

    .modal p {
      margin: 0 0 1.5rem 0;
      color: var(--muted);
      line-height: 1.5;
    }

    .modal-form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .modal-form label {
      font-weight: 500;
      color: #333;
      font-size: 0.95rem;
    }

    .modal-form input {
      padding: 0.75rem;
      border: 1px solid #cde;
      border-radius: 6px;
      font-size: 0.95rem;
      font-family: inherit;
      transition: border-color 0.3s;
    }

    .modal-form input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(36, 102, 164, 0.1);
    }

    .modal-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 1.5rem;
    }

    .modal-actions button {
      padding: 0.65rem 1.5rem;
      border: none;
      border-radius: 6px;
      font-weight: 500;
      cursor: pointer;
      font-size: 0.95rem;
      transition: all 0.3s;
    }

    .btn-modal-cancel {
      background: #f0f0f0;
      color: #333;
    }

    .btn-modal-cancel:hover {
      background: #e0e0e0;
    }

    .btn-modal-submit {
      background: var(--accent);
      color: white;
    }

    .btn-modal-submit:hover {
      background: #1a4d80;
    }

    .modal-error {
      background: #fef5f5;
      border: 1px solid #f5c6cb;
      color: #c33;
      padding: 0.75rem;
      border-radius: 6px;
      margin-bottom: 1rem;
      display: none;
    }

    .modal-error.active {
      display: block;
    }

    /* Book Modal Styles */
    .book-modal {
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
    }

    .book-modal h2 {
      color: var(--accent);
      margin-bottom: 0.5rem;
      font-size: 1.75rem;
    }

    .book-author {
      color: var(--muted);
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }

    .book-meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .book-meta-item {
      padding: 0.75rem;
      background: #f9fafb;
      border-radius: 6px;
    }

    .book-meta-label {
      font-weight: 600;
      color: var(--accent);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.25rem;
    }

    .book-meta-value {
      color: #333;
      font-size: 0.95rem;
    }

    .book-availability {
      padding: 1rem;
      background: #f0f7ff;
      border-left: 4px solid var(--accent);
      border-radius: 4px;
      margin-bottom: 1.5rem;
    }

    .book-availability-title {
      font-weight: 600;
      color: var(--accent);
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .book-availability-status {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--accent);
    }

    .book-availability-status.unavailable {
      color: #c33;
    }

    .book-modal-actions {
      display: flex;
      gap: 1rem;
      justify-content: space-between;
      margin-top: 1.5rem;
    }

    .book-btn-borrow {
      flex: 1;
      padding: 0.75rem;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.95rem;
      transition: background 0.3s;
    }

    .book-btn-borrow:hover:not(:disabled) {
      background: #1a4d80;
    }

    .book-btn-borrow:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    @media (max-width: 880px) {
      .user-menu {
        gap: 0.5rem;
      }
      .user-name {
        font-size: 0.85rem;
      }
      .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
      }
    }
    
    @media (max-width: 600px) {
      .user-name {
        display: none;
      }
      .logout-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.8rem;
      }

      .modal {
        padding: 1.5rem;
        width: 95%;
      }

      .modal h2 {
        font-size: 1.25rem;
      }

      .modal-actions {
        flex-direction: column;
      }

      .modal-actions button {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="#" aria-label="Riverside Library home">
        <svg class="logo" width="40" height="40" viewBox="0 0 24 24" aria-hidden="true">
          <rect x="3" y="4" width="18" height="14" rx="2" fill="#246" />
          <path d="M6 8h12M6 12h8" stroke="#fff" stroke-width="1.2" stroke-linecap="round" />
        </svg>
        <span class="brand-name">ICT 3A Library</span>
      </a>

      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'user' && isUserVerified()): ?>
        <nav class="center-nav" aria-label="User">
          <a class="nav-link" href="user_borrow_history.php">📚 My Borrow History</a>
        </nav>
      <?php endif; ?>

      <nav class="main-nav" aria-label="Primary">
        <p class="welcome">Welcome!</p>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="user-menu">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a class="logout-btn" href="logout.php">Logout</a>
          </div>
        <?php else: ?>
          <a class="btn" href="login_page.php">Login</a>
          <a class="btn" href="register_page.php">Register</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main data-user-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <section class="hero">
      <div class="container hero-grid">
        <div class="hero-content">
          <h1>Explore. Learn. Connect.</h1>
          <p class="lede">Discover books and borrow from our collection without having to sign up!</p>

          <form class="search-form" role="search" aria-label="Search the library catalogue" onsubmit="handleSearch(event)">
            <label for="q" class="sr-only">Search catalogue</label>
            <input id="q" name="q" type="search" placeholder="Search titles, authors, subjects..." />
            <button type="submit" class="search-btn">Search</button>
          </form>
        </div>

        <div class="hero-visual" aria-hidden="true">
          <!-- simple decorative illustration -->
          <svg viewBox="0 0 200 140" width="100%" height="100%" class="illustration">
            <rect x="6" y="10" width="64" height="110" rx="6" fill="#f4f9fb" stroke="#cde" />
            <rect x="78" y="22" width="64" height="98" rx="6" fill="#fff7ea" stroke="#f0d" />
            <rect x="150" y="30" width="40" height="88" rx="6" fill="#eef9f2" stroke="#bfe" />
          </svg>
        </div>
      </div>
    </section>

    <!-- Available Books Section -->
    <section id="booksSection" class="cards container">
      <h2>Collection</h2>
      <p class="muted">Browse and borrow books from our collection!</p>

      <div style="margin-bottom: 1.5rem;">
        <label for="availabilityFilter" style="font-weight: 500; margin-right: 0.5rem;">Sort By:</label>
        <select id="availabilityFilter" style="padding: 0.5rem; border-radius: 6px; border: 1px solid #cde; font-size: 0.95rem; cursor: pointer;">
          <option value="all">All Books</option>
          <option value="available">Available</option>
        </select>
      </div>

      <div class="books-categories" id="booksGrid" aria-live="polite">
        <div style="text-align: center; padding: 2rem;">
          <p>Loading books...</p>
        </div>
      </div>
    </section>
  </main>

  <!-- Borrow Request Modal -->
  <div class="modal-overlay" id="borrowModal">
    <div class="modal">
      <h2 id="modalTitle">Request to Borrow Book</h2>
      <p id="modalMessage"></p>
      
      <div class="modal-error" id="modalError"></div>
      <div class="modal-error" id="modalSuccess" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;"></div>

      <form class="modal-form" id="borrowForm" onsubmit="submitBorrowRequest(event)">
        <div id="guestNameContainer" style="display: none;">
          <label for="guestName">Your Name *</label>
          <input 
            type="text" 
            id="guestName" 
            name="guestName" 
            placeholder="Enter your full name"
            minlength="5"
          />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-modal-cancel" onclick="closeBorrowModal()">Cancel</button>
          <button type="submit" class="btn-modal-submit">Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Book View Modal -->
  <div id="bookViewModal" class="modal-overlay">
    <div class="modal book-modal">
      <div id="bookModalContent" style="text-align: center; padding: 2rem;">Loading...</div>
      <div class="modal-actions">
        <button onclick="closeBookModal()" class="btn-modal-cancel" style="flex: 1;">Close</button>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
    </div>
  </footer>

  <script>
    let allBooks = []; // Store all books for filtering

    // Load books from database and display them by category
    function loadBooks(searchQuery = '', searchType = 'all') {
      let url = searchQuery ? 'search_books.php?q=' + encodeURIComponent(searchQuery) + '&type=' + encodeURIComponent(searchType) : 'get_books.php';
      console.log('Fetching from:', url);
      
      fetch(url)
        .then(response => response.json())
        .then(data => {
          console.log('Data received:', data);
          if (data.success && data.books && data.books.length > 0) {
            allBooks = data.books; // Store all books
            displayBooks(data.books, searchQuery);
          } else {
            document.getElementById('booksGrid').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--muted);"><p>No books found.</p></div>';
          }
        })
        .catch(error => {
          console.error('Error loading books:', error);
          document.getElementById('booksGrid').innerHTML = '<div style="text-align: center; padding: 2rem; color: #c33;"><p>Error loading books. Please try again later.</p></div>';
        });
    }

    function displayBooks(books, searchQuery = '') {
      let html = '';
      
      // Get the current filter value
      const filterValue = document.getElementById('availabilityFilter')?.value || 'all';
      
      // Filter books based on availability
      let filteredBooks = books;
      if (filterValue === 'available') {
        filteredBooks = books.filter(book => book.available > 0);
      } else if (filterValue === 'unavailable') {
        filteredBooks = books.filter(book => book.available === 0);
      }

      if (filteredBooks.length === 0) {
        document.getElementById('booksGrid').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--muted);"><p>No books found.</p></div>';
        return;
      }
      
      // If searching, display all results in one grid
      if (searchQuery) {
        html = `
          <div style="margin-bottom: 2rem;">
            <h3 style="color: var(--accent);">Search Results for "${escapeHtml(searchQuery)}"</h3>
            <p style="color: var(--muted); margin-bottom: 1rem;">Found ${filteredBooks.length} book(s)</p>
            <div class="card-grid">
        `;
        
        filteredBooks.forEach(book => {
          html += `
            <div class="card" style="${filterValue === 'unavailable' ? 'opacity: 0.7; border: 2px solid #ffc0c0;' : ''}">
              <h3>${escapeHtml(book.title)}</h3>
              <p><strong>Author:</strong> ${escapeHtml(book.author)}</p>
              <p><strong>Category:</strong> ${escapeHtml(book.category)}</p>
              ${book.shelf_location ? `<p style="font-size: 0.9rem; color: var(--muted);"><strong>Shelf:</strong> ${escapeHtml(book.shelf_location)}</p>` : ''}
              <p style="font-size: 0.9rem; color: var(--muted);">${book.available > 0 ? `✓ ${book.available} ${book.available === 1 ? 'Copy' : 'Copies'} Available` : '✗ Currently Unavailable'}</p>
              <a href="#" onclick="viewBookModal(${book.id}); return false;" class="card-link">View</a>
            </div>
          `;
        });
        
        html += `
            </div>
            <div style="text-align: center; margin-top: 2rem;">
              <a href="index.php" style="color: var(--accent); text-decoration: none; font-weight: 500;">← Back to All Books</a>
            </div>
          </div>
        `;
      } else {
        // Group books by category for regular view
        const booksByCategory = {};
        filteredBooks.forEach(book => {
          if (!booksByCategory[book.category]) {
            booksByCategory[book.category] = [];
          }
          booksByCategory[book.category].push(book);
        });

        // Generate HTML for categories
        for (const [category, categoryBooks] of Object.entries(booksByCategory)) {
          html += `
            <div class="category-group">
              <h3>${escapeHtml(category)}</h3>
              <div class="card-grid">
          `;
          
          categoryBooks.forEach(book => {
            html += `
              <div class="card" style="${filterValue === 'unavailable' ? 'opacity: 0.7; border: 2px solid #ffc0c0;' : ''}">
                <h3>${escapeHtml(book.title)}</h3>
                <p><strong>Author:</strong> ${escapeHtml(book.author)}</p>
                ${book.shelf_location ? `<p style="font-size: 0.9rem; color: var(--muted);"><strong>Shelf:</strong> ${escapeHtml(book.shelf_location)}</p>` : ''}
                <p style="font-size: 0.9rem; color: var(--muted);">${book.available > 0 ? `✓ ${book.available} ${book.available === 1 ? 'Copy' : 'Copies'} Available` : '✗ Currently Unavailable'}</p>
                <a href="#" onclick="viewBookModal(${book.id}); return false;" class="card-link">View</a>
              </div>
            `;
          });

          html += `
              </div>
            </div>
          `;
        }
      }

      document.getElementById('booksGrid').innerHTML = html;
    }
    
    function handleSearch(event) {
      event.preventDefault();
      const searchQuery = document.getElementById('q').value.trim();
      if (searchQuery) {
        loadBooks(searchQuery, 'all');
      }
    }

    let currentBookId = null;

    function borrowBook(bookId) {
      const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
      currentBookId = bookId;
      
      const modal = document.getElementById('borrowModal');
      const guestNameContainer = document.getElementById('guestNameContainer');
      const modalTitle = document.getElementById('modalTitle');
      const modalMessage = document.getElementById('modalMessage');
      const borrowForm = document.getElementById('borrowForm');
      const modalError = document.getElementById('modalError');

      // Reset form and error
      borrowForm.reset();
      modalError.classList.remove('active');

      if (isLoggedIn) {
        // For logged-in users
        modalTitle.textContent = 'Request to Borrow Book';
        modalMessage.textContent = 'Your borrow request will be sent to the librarians for approval. You will be notified once it\'s approved.';
        guestNameContainer.style.display = 'none';
      } else {
        // For guest users
        modalTitle.textContent = 'Guest Borrow Request';
        modalMessage.textContent = 'Please enter your name to submit a borrow request. Our librarians will contact you once your request is approved.';
        guestNameContainer.style.display = 'block';
        document.getElementById('guestName').focus();
      }

      // Close book view modal if it's open
      closeBookModal();
      modal.classList.add('active');
    }

    function closeBorrowModal() {
      const modal = document.getElementById('borrowModal');
      modal.classList.remove('active');
      currentBookId = null;
    }

    function submitBorrowRequest(event) {
      event.preventDefault();
      
      const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
      const modalError = document.getElementById('modalError');
      const guestNameInput = document.getElementById('guestName');
      const guestName = guestNameInput ? guestNameInput.value.trim() : '';

      // Reset error
      modalError.classList.remove('active');

      // Validate guest name if guest
      if (!isLoggedIn) {
        if (!guestName || guestName.length < 5) {
          modalError.textContent = 'Please enter a valid name (at least 5 characters)';
          modalError.classList.add('active');
          if (guestNameInput) guestNameInput.focus();
          return;
        }
      }

      const formData = new FormData();
      formData.append('book_id', currentBookId);
      if (!isLoggedIn) {
        formData.append('guest_name', guestName);
      }

      fetch('borrow_request.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const modalSuccess = document.getElementById('modalSuccess');
            const modalError = document.getElementById('modalError');
            modalError.classList.remove('active');
            modalSuccess.textContent = 'Borrow request submitted! An admin will review your request shortly.';
            modalSuccess.classList.add('active');
            setTimeout(() => {
              closeBorrowModal();
            }, 2000);
          } else {
            const modalError = document.getElementById('modalError');
            modalError.textContent = 'Error: ' + data.message;
            modalError.classList.add('active');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          modalError.textContent = 'Error submitting borrow request. Please try again.';
          modalError.classList.add('active');
        });
    }

    // Close modal when clicking outside of it
    document.addEventListener('click', (event) => {
      const modal = document.getElementById('borrowModal');
      if (event.target === modal) {
        closeBorrowModal();
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeBorrowModal();
      }
    });

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Load books when page loads
    document.addEventListener('DOMContentLoaded', () => {
      loadBooks();
      
      // Add event listener for filter changes
      const filterSelect = document.getElementById('availabilityFilter');
      if (filterSelect) {
        filterSelect.addEventListener('change', () => {
          displayBooks(allBooks);
        });
      }
    });
    
    // Also call it immediately in case DOM is already loaded
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        loadBooks();
        
        const filterSelect = document.getElementById('availabilityFilter');
        if (filterSelect) {
          filterSelect.addEventListener('change', () => {
            displayBooks(allBooks);
          });
        }
      });
    } else {
      loadBooks();
      
      const filterSelect = document.getElementById('availabilityFilter');
      if (filterSelect) {
        filterSelect.addEventListener('change', () => {
          displayBooks(allBooks);
        });
      }
    }

    // Book Modal Functions
    function viewBookModal(bookId) {
      const modal = document.getElementById('bookViewModal');
      const content = document.getElementById('bookModalContent');
      
      // Fetch book details
      fetch('view_book.php?id=' + bookId + '&ajax=1')
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            content.innerHTML = '<p style="color: #c33;">Error loading book details</p>';
            return;
          }
          
          const book = data.book;
          let html = `
            <h2>${escapeHtml(book.title)}</h2>
            <p class="book-author">By ${escapeHtml(book.author)}</p>
            
            <div class="book-meta">
              <div class="book-meta-item">
                <div class="book-meta-label">Author</div>
                <div class="book-meta-value">${escapeHtml(book.author)}</div>
              </div>
              
              <div class="book-meta-item">
                <div class="book-meta-label">Category</div>
                <div class="book-meta-value">${escapeHtml(book.category)}</div>
              </div>
          `;
          
          if (book.isbn) {
            html += `
              <div class="book-meta-item">
                <div class="book-meta-label">ISBN</div>
                <div class="book-meta-value">${escapeHtml(book.isbn)}</div>
              </div>
            `;
          }
          
          if (book.shelf_location) {
            html += `
              <div class="book-meta-item">
                <div class="book-meta-label">Shelf Location</div>
                <div class="book-meta-value">${escapeHtml(book.shelf_location)}</div>
              </div>
            `;
          }
          
          html += `
              <div class="book-meta-item">
                <div class="book-meta-label">Total Copies</div>
                <div class="book-meta-value">${book.quantity}</div>
              </div>
              
              <div class="book-meta-item">
                <div class="book-meta-label">Available</div>
                <div class="book-meta-value">${book.available}</div>
              </div>
            </div>
            
            <div class="book-availability">
              <div class="book-availability-title">Availability Status</div>
          `;
          
          if (book.available > 0) {
            html += `
              <div class="book-availability-status">
                ✓ ${book.available} Copy/Copies Available
              </div>
            `;
          } else {
            html += `
              <div class="book-availability-status unavailable">
                ✗ Currently Unavailable
              </div>
            `;
          }
          
          html += `
            </div>
          `;
          
          // Add borrow button if available
          if (book.available > 0) {
            html += `<button class="book-btn-borrow" onclick="borrowBook(${book.id})">Borrow This Book</button>`;
          } else {
            html += `<button class="book-btn-borrow" disabled>Currently Unavailable</button>`;
          }
          
          content.innerHTML = html;
        })
        .catch(error => {
          console.error('Error:', error);
          content.innerHTML = '<p style="color: #c33;">Error loading book details</p>';
        });
      
      modal.classList.add('active');
    }

    function closeBookModal() {
      document.getElementById('bookViewModal').classList.remove('active');
    }

    // Close modals on overlay click
    document.addEventListener('click', (event) => {
      const bookModal = document.getElementById('bookViewModal');
      const borrowModal = document.getElementById('borrowModal');
      if (event.target === bookModal) {
        closeBookModal();
      }
      if (event.target === borrowModal) {
        closeBorrowModal();
      }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeBookModal();
        closeBorrowModal();
      }
    });
  </script>
</body>
</html>