<?php
session_start();
require_once 'connect.php';

// Get book ID from URL
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    $_SESSION['error'] = 'Invalid book ID';
    header('Location: catalog_page.php');
    exit();
}

// Fetch book details with copy information
$query = $conn->prepare('SELECT b.id, b.title, b.isbn, b.author, b.category_id, b.shelf_location_id, COALESCE(bc_cat.category_name, b.category, "Uncategorized") as category, COALESCE(sl.location_code, b.shelf_location, "N/A") as shelf_location, 
             COUNT(bc.id) as quantity, 
             SUM(CASE WHEN bc.status = "available" THEN 1 ELSE 0 END) as available
           FROM books b
           LEFT JOIN book_copies bc ON b.id = bc.book_id
           LEFT JOIN book_categories bc_cat ON b.category_id = bc_cat.id
           LEFT JOIN shelf_locations sl ON b.shelf_location_id = sl.id
           WHERE b.id = ?
           GROUP BY b.id');
$query->bind_param('i', $book_id);
$query->execute();
$result = $query->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    $_SESSION['error'] = 'Book not found';
    header('Location: catalog_page.php');
    exit();
}

// Fetch all book copies
$copies_query = $conn->prepare('SELECT id, accession_code, copy_number, status FROM book_copies WHERE book_id = ? ORDER BY copy_number ASC');
$copies_query->bind_param('i', $book_id);
$copies_query->execute();
$copies_result = $copies_query->get_result();
$book_copies = [];
while ($copy = $copies_result->fetch_assoc()) {
    $book_copies[] = $copy;
}
$copies_query->close();

$query->close();

// Load categories and shelf locations for selects
$categories = [];
$cat_q = $conn->query('SELECT id, category_name FROM book_categories ORDER BY category_name ASC');
if ($cat_q) {
  while ($r = $cat_q->fetch_assoc()) $categories[] = $r;
}

$locations = [];
$loc_q = $conn->query('SELECT id, location_code, description FROM shelf_locations ORDER BY location_code ASC');
if ($loc_q) {
  while ($r = $loc_q->fetch_assoc()) $locations[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Book — ICT 3A Library</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .edit-container {
      max-width: 600px;
      margin: 3rem auto;
      padding: 2rem;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .edit-container h1 {
      color: var(--accent);
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

    .form-group input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.95rem;
      box-sizing: border-box;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 4px rgba(34, 70, 102, 0.2);
    }

    .form-group select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.95rem;
      box-sizing: border-box;
    }

    .form-group select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 4px rgba(34, 70, 102, 0.2);
    }

    .button-group {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .btn {
      flex: 1;
      padding: 0.75rem;
      border: none;
      border-radius: 4px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-primary {
      background: var(--accent);
      color: white;
    }

    .btn-primary:hover {
      background: #1a4d80;
    }

    /* Auto-generate controls and accession inputs */
    .new-copy-input input,
    .new-accession-code {
      font-family: monospace;
      letter-spacing: 0.08em;
    }

    #edit_auto_generate {
      width: 16px;
      height: 16px;
      accent-color: var(--accent);
    }

    #edit_auto_generate_count {
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 0.35rem 0.5rem;
      width: 80px;
    }

    .btn-secondary {
      background: #f0f0f0;
      color: #333;
    }

    .btn-secondary:hover {
      background: #e0e0e0;
    }

    .btn-archive {
      background: #fff3e0;
      color: #e65100;
    }

    .btn-archive:hover {
      background: #ffe0b2;
    }

    .alert {
      padding: 1rem;
      border-radius: 4px;
      margin-bottom: 1.5rem;
    }

    .alert-error {
      background: #fee;
      color: #c33;
      border: 1px solid #fcc;
    }

    .logout-btn {
      background: var(--accent);
      color: white;
      padding: 0.45rem 0.7rem;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.95rem;
      margin-left: auto;
    }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-left: auto;
    }

    header .container {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .status-badge {
      padding: 0.3rem 0.6rem;
      border-radius: 4px;
      font-size: 0.85rem;
      cursor: pointer;
      display: inline-block;
      transition: all 0.2s;
      border: 1px solid transparent;
    }

    .status-badge:hover {
      border-color: var(--accent);
      transform: scale(1.05);
    }

    .status-available {
      background: #d4edda;
      color: #155724;
    }

    .status-damaged {
      background: #fff3cd;
      color: #856404;
    }

    .status-lost {
      background: #f8d7da;
      color: #721c24;
    }

    .status-archived {
      background: #e9ecef;
      color: #495057;
    }

    .status-menu {
      position: fixed;
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      z-index: 10000;
      min-width: 130px;
      display: none;
    }

    .status-menu button {
      display: block;
      width: 100%;
      padding: 0.6rem 1rem;
      border: none;
      background: none;
      cursor: pointer;
      text-align: left;
      font-size: 0.9rem;
      transition: background 0.2s;
    }

    .status-menu button:hover {
      background: #f0f0f0;
    }

    .status-menu button:first-child {
      border-top-left-radius: 4px;
      border-top-right-radius: 4px;
    }

    .status-menu button:last-child {
      border-bottom-left-radius: 4px;
      border-bottom-right-radius: 4px;
    }


  </style>
</head>
<body>
  <?php include 'header_template.php'; ?>

  <main>
    <div class="edit-container">
      <h1>Edit Book</h1>

      <?php
      if (isset($_SESSION['error'])) {
          echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
          unset($_SESSION['error']);
      }
      ?>

      <form id="editBookForm" method="POST" action="edit_book_process.php" onsubmit="return validateEditBookForm(event)">
        <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book['id']); ?>" />

        <div class="form-group">
          <label for="title">Book Title</label>
          <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($book['title']); ?>" />
        </div>

        <div class="form-group">
          <label for="isbn">ISBN</label>
          <input type="text" id="isbn" name="isbn" required pattern="^(?!.*--)(?:[0-9]-?){12}[0-9]$" title="ISBN-13: exactly 13 digits (dashes allowed, but not consecutive). Example: 978-0-553-38016-3" placeholder="e.g., 978-0-123456-78-9" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>" />
        </div>

        <div class="form-group">
          <label for="author">Author</label>
          <input type="text" id="author" name="author" required value="<?php echo htmlspecialchars($book['author']); ?>" />
        </div>

        <div class="form-group">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required>
            <option value="">-- Select a Category --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo (isset($book['category_id']) && $book['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="shelf_location_id">Shelf Location</label>
          <select id="shelf_location_id" name="shelf_location_id">
            <option value="">-- Select a Location --</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?php echo $loc['id']; ?>" <?php echo (isset($book['shelf_location_id']) && $book['shelf_location_id'] == $loc['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($loc['location_code'] . ($loc['description'] ? ' - ' . $loc['description'] : '')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Book Copies (<?php echo count($book_copies); ?> total, <?php echo $book['available']; ?> available)</label>
          <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; max-height: 300px; overflow-y: auto;">
            <?php if (count($book_copies) > 0): ?>
              <table style="width: 100%; font-size: 0.9rem;">
                <tr style="border-bottom: 1px solid #cde;">
                  <th style="text-align: left; padding: 0.5rem;">Copy #</th>
                  <th style="text-align: left; padding: 0.5rem;">Accession Code</th>
                  <th style="text-align: left; padding: 0.5rem;">Status</th>
                </tr>
                <?php foreach ($book_copies as $copy): ?>
                <tr style="border-bottom: 1px solid #e0e0e0;">
                  <td style="padding: 0.5rem;"><?php echo $copy['copy_number']; ?></td>
                  <td style="padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($copy['accession_code']); ?></td>
                  <td style="padding: 0.5rem; position: relative;">
                    <?php if ($copy['status'] !== 'borrowed'): ?>
                    <div class="status-badge status-<?php echo $copy['status']; ?>" onclick="toggleStatusMenu(event, <?php echo $copy['id']; ?>, '<?php echo htmlspecialchars($copy['status']); ?>')">
                      <?php echo ucfirst($copy['status']); ?> ▼
                    </div>
                    <div id="menu-<?php echo $copy['id']; ?>" class="status-menu">
                      <button type="button" onclick="updateCopyStatus(<?php echo $copy['id']; ?>, 'available', event)">Available</button>
                      <button type="button" onclick="updateCopyStatus(<?php echo $copy['id']; ?>, 'damaged', event)">Damaged</button>
                      <button type="button" onclick="updateCopyStatus(<?php echo $copy['id']; ?>, 'lost', event)">Lost</button>
                      <button type="button" onclick="updateCopyStatus(<?php echo $copy['id']; ?>, 'archived', event)">Archived</button>
                    </div>
                    <?php else: ?>
                    <div class="status-badge status-borrowed" title="Currently borrowed">Borrowed</div>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </table>
            <?php else: ?>
              <p style="color: var(--muted);">No copies found</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Add New Copies Section -->
        <div class="form-group">
          <label>Add New Copies to This Book</label>
          <div style="background: #f0f8ff; padding: 1.5rem; border-radius: 6px; border: 1px solid #b3d9ff;">
            <p style="margin-top: 0; color: #333; font-size: 0.95rem;">Add additional copies with their accession codes:</p>
              <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                <input type="checkbox" id="edit_auto_generate" name="edit_auto_generate" />
                <label for="edit_auto_generate" style="margin:0; font-size:0.95rem;">Auto-generate accession codes</label>
                <label for="edit_auto_generate_count" style="margin-left:1rem; font-size:0.95rem;">Count:</label>
                <input type="number" id="edit_auto_generate_count" name="edit_auto_generate_count" value="1" min="1" style="width:80px; padding:0.4rem;" />
              </div>
              <div id="new_copies_container">
                <div class="new-copy-input" style="margin-bottom: 0.75rem;">
                  <input type="text" class="new-accession-code" placeholder="Enter 4-digit accession code" maxlength="4" style="width: 100%; padding: 0.75rem; border: 1px solid #cde; border-radius: 4px; font-family: monospace;" />
                </div>
              </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
              <button type="button" class="btn btn-primary" style="flex: 1; padding: 0.5rem;" onclick="addNewCopyInput()">+ Add Another Code</button>
              <button type="button" class="btn btn-secondary" style="flex: 1; padding: 0.5rem;" onclick="clearNewCopies()">Clear All</button>
            </div>
          </div>
          <input type="hidden" id="new_accession_codes" name="new_accession_codes" value="" />
          <input type="hidden" id="new_auto_generate" name="new_auto_generate" value="0" />
          <input type="hidden" id="new_auto_generate_count" name="new_auto_generate_count" value="0" />
        </div>

        <div class="button-group">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary" onclick="window.location.href='catalog_page.php'">Cancel</button>
        </div>
      </form>
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
      <p>© ICT 3A Library • <a href="#">Privacy</a> • <a href="#">Accessibility</a></p>
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

    let currentMenu = null;

    function toggleStatusMenu(event, copyId, currentStatus) {
      event.stopPropagation();
      
      // Close previous menu if open
      if (currentMenu && currentMenu !== `menu-${copyId}`) {
        document.getElementById(currentMenu).style.display = 'none';
      }
      
      const menu = document.getElementById(`menu-${copyId}`);
      const badge = event.currentTarget;
      const rect = badge.getBoundingClientRect();
      
      if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
        menu.style.top = (rect.bottom + 5) + 'px';
        menu.style.left = rect.left + 'px';
        currentMenu = `menu-${copyId}`;
      } else {
        menu.style.display = 'none';
        currentMenu = null;
      }
    }

    function updateCopyStatus(copyId, newStatus, event) {
      event.preventDefault();
      event.stopPropagation();
      
      // Close menu
      const menu = document.getElementById(`menu-${copyId}`);
      menu.style.display = 'none';
      
      // Send update request
      fetch('update_copy_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `copy_id=${copyId}&status=${encodeURIComponent(newStatus)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to show updated status
          location.reload();
        } else {
          showModal('Error', 'Error updating status: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showModal('Error', 'Error updating status');
      });
    }

    // Close menu when clicking outside
    document.addEventListener('click', function() {
      if (currentMenu) {
        const menu = document.getElementById(currentMenu);
        menu.style.display = 'none';
        currentMenu = null;
      }
    });

    function addNewCopyInput() {
      const container = document.getElementById('new_copies_container');
      const newInput = document.createElement('div');
      newInput.className = 'new-copy-input';
      newInput.style.marginBottom = '0.75rem';
      newInput.innerHTML = '<input type="text" class="new-accession-code" placeholder="Enter 4-digit accession code" maxlength="4" style="width: 100%; padding: 0.75rem; border: 1px solid #cde; border-radius: 4px; font-family: monospace;" >';
      container.appendChild(newInput);
    }

    function clearNewCopies() {
      const container = document.getElementById('new_copies_container');
      container.innerHTML = '<div class="new-copy-input" style="margin-bottom: 0.75rem;"><input type="text" class="new-accession-code" placeholder="Enter 4-digit accession code" maxlength="4" style="width: 100%; padding: 0.75rem; border: 1px solid #cde; border-radius: 4px; font-family: monospace;" /></div>';
    }

    // Handle form submission to collect and validate accession codes
    document.querySelector('form').addEventListener('submit', function(event) {
      const autoCheckbox = document.getElementById('edit_auto_generate');
      const autoCountInput = document.getElementById('edit_auto_generate_count');
      const codes = [];

      if (autoCheckbox && autoCheckbox.checked) {
        // When auto-generate is used, set hidden flags and count
        document.getElementById('new_auto_generate').value = '1';
        document.getElementById('new_auto_generate_count').value = Math.max(1, parseInt(autoCountInput.value) || 1);
        document.getElementById('new_accession_codes').value = '';
        return true;
      }

      // Manual mode: collect codes
      const inputs = document.querySelectorAll('.new-accession-code');
      for (let input of inputs) {
        const code = input.value.trim();
        if (code) {
          if (!/^\d{4}$/.test(code)) {
            event.preventDefault();
            showModal('Error', 'All accession codes must be exactly 4 digits.');
            return false;
          }
          codes.push(code);
        }
      }

      document.getElementById('new_auto_generate').value = '0';
      document.getElementById('new_auto_generate_count').value = '0';
      document.getElementById('new_accession_codes').value = codes.join(',');
      return true;
    });
  </script>
  <script>
    // Client-side validation for Edit Book form
    function validateEditBookForm(event) {
      const isbnField = document.querySelector('#editBookForm #isbn') || document.getElementById('isbn');
      const isbn = (isbnField && isbnField.value) ? isbnField.value.trim() : '';
      const isbnRegex = /^(?!.*--)(?:[0-9]-?){12}[0-9]$/;
      if (!isbnRegex.test(isbn)) {
        // Use server modal if available
        if (typeof showModal === 'function') {
          showModal('Error', 'ISBN must contain exactly 13 digits (dashes allowed, but not consecutive).', 'error');
        } else {
          alert('ISBN must contain exactly 13 digits (dashes allowed, but not consecutive).');
        }
        event.preventDefault();
        return false;
      }
      return true;
    }
  </script>
</body>
</html>
