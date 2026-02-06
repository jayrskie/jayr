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
  <title>Catalog Management — ICT 3A Library</title>
  <meta name="description" content="Manage library catalog - add, edit, and archive books" />
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
      flex-wrap: wrap;
      justify-content: flex-end;
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
      white-space: nowrap;
    }
    .logout-btn:hover {
      background: #1a4d80;
    }

    /* Notification badge styles moved to styles.css */

    .catalog-container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .catalog-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .catalog-header h1 {
      margin: 0;
      color: var(--accent);
    }

    .action-buttons {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .btn-primary {
      background: var(--accent);
      color: white;
      padding: 0.6rem 1rem;
      border-radius: 8px;
      text-decoration: none;
      border: none;
      cursor: pointer;
      font-size: 0.95rem;
      transition: background 0.3s;
    }

    .btn-primary:hover {
      background: #1a4d80;
    }

    .btn-secondary {
      background: transparent;
      border: 2px solid var(--accent);
      color: var(--accent);
      padding: 0.5rem 0.9rem;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      font-size: 0.95rem;
      transition: all 0.3s;
    }

    .btn-secondary:hover {
      background: var(--accent);
      color: white;
    }

    .tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 2rem;
      border-bottom: 2px solid #e0e0e0;
    }

    .tab-btn {
      background: none;
      border: none;
      padding: 1rem;
      font-size: 1rem;
      color: var(--muted);
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: all 0.3s;
    }

    .tab-btn.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .form-section {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: #333;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.95rem;
      box-sizing: border-box;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 4px rgba(34, 70, 102, 0.2);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .alert {
      padding: 1rem;
      border-radius: 4px;
      margin-bottom: 1.5rem;
    }

    .alert-success {
      background: #efe;
      color: #3c3;
      border: 1px solid #cfc;
    }

    .alert-error {
      background: #fee;
      color: #c33;
      border: 1px solid #fcc;
    }

    .books-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .books-table thead {
      background: #f9f9f9;
      border-bottom: 2px solid #e0e0e0;
    }

    .books-table th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: var(--accent);
    }

    .books-table td {
      padding: 1rem;
      border-bottom: 1px solid #f0f0f0;
    }

    .books-table tbody tr:hover {
      background: #f9f9f9;
    }

    .action-icons {
      display: flex;
      gap: 0.5rem;
    }

    .action-btn {
      padding: 0.4rem 0.6rem;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.85rem;
      cursor: pointer;
      border: none;
      transition: all 0.3s;
    }

    .btn-edit {
      background: #e8f5e9;
      color: #2e7d32;
    }

    .btn-edit:hover {
      background: #c8e6c9;
    }

    .btn-archive {
      background: #fff3e0;
      color: #e65100;
    }

    .btn-archive:hover {
      background: #ffe0b2;
    }

    .btn-restore {
      background: #e3f2fd;
      color: #1565c0;
    }

    .btn-restore:hover {
      background: #bbdefb;
    }

    .csv-help {
      background: #f5f5f5;
      padding: 1rem;
      border-radius: 4px;
      margin-top: 1rem;
      font-size: 0.9rem;
      color: #666;
    }

    .csv-help h4 {
      margin-top: 0;
      color: var(--accent);
    }

    @media (max-width: 880px) {
      .catalog-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .action-buttons {
        width: 100%;
      }

      .books-table {
        font-size: 0.9rem;
      }

      .books-table th,
      .books-table td {
        padding: 0.75rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'header_template.php'; ?>

  <main>
    <div class="catalog-container">
      <div class="catalog-header">
        <h1>📚 Catalog Management</h1>
        <div class="action-buttons">
          <button class="btn-primary" onclick="switchTab('add-single')">Add Single Book</button>
          <button class="btn-secondary" onclick="switchTab('bulk-import')">Bulk Import (CSV)</button>
        </div>
      </div>

      <?php
      if (isset($_SESSION['success'])) {
          $message = htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
          echo '<script>showModal("Success", ' . json_encode($message) . ', "success");</script>';
          unset($_SESSION['success']);
      }
      if (isset($_SESSION['error'])) {
          $message = htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
          echo '<script>showModal("Error", ' . json_encode($message) . ', "error");</script>';
          unset($_SESSION['error']);
      }
      ?>

      <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('add-single')">Add Single Book</button>
        <button class="tab-btn" onclick="switchTab('bulk-import')">Bulk Import</button>
        <button class="tab-btn" onclick="switchTab('manage-books')">Manage Books</button>
      </div>

      <!-- Add Single Book Tab -->
      <div id="add-single" class="tab-content active">
        <div class="form-section">
          <h2>Add a New Book</h2>
          <form method="POST" action="add_book_process.php">
            <div class="form-row">
              <div class="form-group">
                <label for="title">Book Title <span style="color: #c33;">*</span></label>
                <input type="text" id="title" name="title" required placeholder="Enter book title" />
              </div>
              <div class="form-group">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="isbn" placeholder="e.g., 978-0-123456-78-9" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="author">Author <span style="color: #c33;">*</span></label>
                <input type="text" id="author" name="author" required placeholder="Enter author name" />
              </div>
              <div class="form-group">
                <label for="category">Category <span style="color: #c33;">*</span></label>
                <input type="text" id="category" name="category" required placeholder="e.g., Fiction, Science, History" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="shelf_location">Shelf Location</label>
                <input type="text" id="shelf_location" name="shelf_location" placeholder="e.g., FIC-A1, SCI-B2" />
              </div>
              <div class="form-group">
                <label for="quantity">Quantity <span style="color: #c33;">*</span></label>
                <input type="number" id="quantity" name="quantity" required placeholder="Number of copies" min="1" onchange="updateAccessionCodeRows()" />
              </div>
            </div>

            <div class="form-group">
              <label for="accession_codes">Accession Codes <span style="color: #c33;">*</span></label>
              <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem;">Enter one accession code per line (7 digits each). Must match quantity above.</p>
              <textarea id="accession_codes" name="accession_codes" required placeholder="0123456&#10;1234567&#10;2345678" style="width: 100%; height: 100px; padding: 0.75rem; border: 1px solid #cde; border-radius: 6px; font-family: monospace; font-size: 0.9rem;"></textarea>
            </div>

            <button type="submit" class="btn-primary">Add Book</button>
          </form>
        </div>
      </div>

      <!-- Bulk Import Tab -->
      <div id="bulk-import" class="tab-content">
        <div class="form-section">
          <h2>Bulk Import Books (CSV)</h2>
          <form method="POST" action="bulk_import_process.php" enctype="multipart/form-data">
            <div class="form-group">
              <label for="csv_file">Select CSV File <span style="color: #c33;">*</span></label>
              <input type="file" id="csv_file" name="csv_file" accept=".csv" required />
            </div>

            <button type="submit" class="btn-primary">Import Books</button>
          </form>

          <div class="csv-help">
            <h4>CSV Format Guide</h4>
            <p><strong>Required columns:</strong> title, isbn, author, category, accession_code</p>
            <p><strong>Optional columns:</strong> shelf_location</p>
            <p><strong>Note:</strong> Each row adds ONE copy. Books with the same ISBN will share the same book record.</p>
            <p><strong>Example CSV format:</strong></p>
            <code style="display: block; background: white; padding: 0.75rem; border-radius: 4px; margin-top: 0.5rem; overflow-x: auto;">
              title,isbn,author,category,accession_code,shelf_location<br>
              The Great Gatsby,978-0-7432-7356-5,F. Scott Fitzgerald,Fiction,0123456789,FIC-A1<br>
              A Brief History of Time,978-0-553-38016-3,Stephen Hawking,Science,0987654321,SCI-B2<br>
              A Brief History of Time,978-0-553-38016-3,Stephen Hawking,Science,0987654322,SCI-B2
            </code>
          </div>
        </div>
      </div>

      <!-- Manage Books Tab -->
      <div id="manage-books" class="tab-content">
        <div class="form-section">
          <h2>Manage Books</h2>
          <div class="form-group">
            <label for="searchFilter">Search Books:</label>
            <input type="text" id="searchFilter" placeholder="Search by title, author, category, or accession code..." />
          </div>

          <table class="books-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>ISBN</th>
                <th>Author</th>
                <th>Category</th>
                <th>Shelf Location</th>
                <th>Quantity</th>
                <th>Available</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="booksTableBody">
              <!-- Books will be loaded here via AJAX -->
              <tr>
                <td colspan="8" style="text-align: center; color: var(--muted);">Loading books...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal -->
  <div id="alertModal" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Message</h2>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-footer">
        <button class="modal-button modal-button-primary" onclick="closeModal()">OK</button>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p>© ICT 3A Library • <a href="privacy_page.php">Privacy</a> • <a href="accessibility_page.php">Accessibility</a></p>
    </div>
  </footer>

  <script>
    // Modal functions
    function showModal(title, message, type = 'info') {
      const modal = document.getElementById('alertModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');
      
      modalTitle.textContent = title;
      modalBody.textContent = message;
      
      // Remove all alert classes
      modalBody.className = 'modal-body';
      
      // Add appropriate class based on type
      if (type === 'success') {
        modalBody.classList.add('modal-alert-success');
      } else if (type === 'error') {
        modalBody.classList.add('modal-alert-error');
      } else if (type === 'warning') {
        modalBody.classList.add('modal-alert-warning');
      } else {
        modalBody.classList.add('modal-alert-info');
      }
      
      modal.classList.add('show');
    }

    function closeModal() {
      const modal = document.getElementById('alertModal');
      modal.classList.remove('show');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
      const modal = document.getElementById('alertModal');
      if (event.target === modal) {
        closeModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeModal();
      }
    });
    // Helper function to escape HTML
    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Fetch and update pending requests count
    function updatePendingBadge() {
      fetch('get_pending_count.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const badge = document.getElementById('pendingBadge');
            const count = data.pending_count;
            
            if (count > 0) {
              badge.textContent = count;
              badge.classList.remove('hidden');
            } else {
              badge.classList.add('hidden');
            }
          }
        })
        .catch(error => console.error('Error fetching pending count:', error));
    }

    // Update badge on page load
    document.addEventListener('DOMContentLoaded', () => {
      updatePendingBadge();
      // Check for new requests every 30 seconds
      setInterval(updatePendingBadge, 30000);
    });

    function switchTab(tabName) {
      // Hide all tabs
      const tabContents = document.querySelectorAll('.tab-content');
      tabContents.forEach(tab => tab.classList.remove('active'));

      // Deactivate all buttons
      const tabButtons = document.querySelectorAll('.tab-btn');
      tabButtons.forEach(btn => btn.classList.remove('active'));

      // Show selected tab
      const selectedTab = document.getElementById(tabName);
      if (selectedTab) {
        selectedTab.classList.add('active');
      }

      // Activate corresponding button
      const buttons = document.querySelectorAll('.tab-btn');
      buttons.forEach(btn => {
        if (btn.textContent.toLowerCase().includes(tabName.replace('-', ' '))) {
          btn.classList.add('active');
        }
      });

      // Load books table if switching to manage-books
      if (tabName === 'manage-books') {
        loadBooks();
      }
    }

    function updateAccessionCodeRows() {
      const quantity = parseInt(document.getElementById('quantity').value) || 0;
      const textarea = document.getElementById('accession_codes');
      if (quantity > 0) {
        const lines = textarea.value.split('\n').filter(line => line.trim());
        textarea.placeholder = `Enter ${quantity} accession codes (7 digits each, one per line)`;
      }
    }

    function loadBooks() {
      fetch('get_books_admin.php')
        .then(response => response.json())
        .then(data => {
          const tbody = document.getElementById('booksTableBody');
          if (data.success && data.books.length > 0) {
            // Sort books by most recently added/modified first
            const sortedBooks = [...data.books].sort((a, b) => {
              const dateA = new Date(a.created_at || 0);
              const dateB = new Date(b.created_at || 0);
              return dateB - dateA; // Descending order (most recent first)
            });
            
            tbody.innerHTML = sortedBooks.map(book => {
              let actionButtons = '';
              
              actionButtons += `<button class="action-btn btn-edit" onclick="editBook(${book.id})">Edit</button>`;
              
              return `
                <tr>
                  <td>${escapeHtml(book.title)}</td>
                  <td style="font-family: monospace;">${escapeHtml(book.isbn || '-')}</td>
                  <td>${escapeHtml(book.author)}</td>
                  <td>${escapeHtml(book.category)}</td>
                  <td>${escapeHtml(book.shelf_location || '-')}</td>
                  <td>${book.quantity}</td>
                  <td>${book.available}</td>
                  <td>
                    <div class="action-icons">
                      ${actionButtons}
                    </div>
                  </td>
                </tr>
              `;
            }).join('');
          } else {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: var(--muted);">No books found</td></tr>';
          }
        })
        .catch(error => {
          console.error('Error loading books:', error);
          document.getElementById('booksTableBody').innerHTML = '<tr><td colspan="9" style="text-align: center; color: #c33;">Error loading books</td></tr>';
        });
    }

    function editBook(bookId) {
      window.location.href = `edit_book.php?id=${bookId}`;
    }

    // Search functionality - fetch from backend
    document.getElementById('searchFilter').addEventListener('keyup', function() {
      const filter = this.value.trim();
      
      if (filter === '') {
        // If search is empty, reload all books
        loadBooks();
      } else {
        // Search books
        fetch('search_books_admin.php?q=' + encodeURIComponent(filter))
          .then(response => response.json())
          .then(data => {
            const tbody = document.getElementById('booksTableBody');
            if (data.success && data.books.length > 0) {
              // Sort search results by most recently added/modified first
              const sortedBooks = [...data.books].sort((a, b) => {
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA; // Descending order (most recent first)
              });
              
              tbody.innerHTML = sortedBooks.map(book => {
                const isArchived = parseInt(book.archived) === 1;
                let actionButtons = '';
                
                if (!isArchived) {
                  actionButtons += `<button class="action-btn btn-edit" onclick="editBook(${book.id})">Edit</button>`;
                }
                
                if (isArchived) {
                  actionButtons += `<button class="action-btn btn-restore" onclick="toggleArchive(${book.id}, true)">Restore</button>`;
                } else {
                  actionButtons += `<button class="action-btn btn-archive" onclick="toggleArchive(${book.id}, false)">Archive</button>`;
                }
                
                return `
                  <tr>
                    <td>${escapeHtml(book.title)}</td>
                    <td>${escapeHtml(book.author)}</td>
                    <td>${escapeHtml(book.category)}</td>
                    <td>${escapeHtml(book.shelf_location || '-')}</td>
                    <td>${escapeHtml(book.accession_code)}</td>
                    <td>${book.quantity}</td>
                    <td>${book.available}</td>
                    <td><span style="color: ${isArchived ? '#e65100' : '#2e7d32'}; font-weight: 500;">${isArchived ? 'Archived' : 'Active'}</span></td>
                    <td>
                      <div class="action-icons">
                        ${actionButtons}
                      </div>
                    </td>
                  </tr>
                `;
              }).join('');
            } else {
              tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: var(--muted);">No books found</td></tr>';
            }
          })
          .catch(error => {
            console.error('Error searching books:', error);
          });
      }
    });
  </script>
</body>
</html>
