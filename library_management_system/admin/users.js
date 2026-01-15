// Modal elements
const addUserBtn = document.getElementById('addUserBtn');
const addUserModal = document.getElementById('addUserModal');
const modalClose = document.querySelector('.modal-close');
const cancelBtn = document.getElementById('cancelBtn');
const addUserForm = document.getElementById('addUserForm');
const usersTable = document.getElementById('usersTable');
const searchInput = document.getElementById('searchInput');

// Edit modal elements
const editUserModal = document.getElementById('editUserModal');
const editModalClose = document.getElementById('editModalClose');
const editCancelBtn = document.getElementById('editCancelBtn');
const editUserForm = document.getElementById('editUserForm');

let users = []; // Store users in memory

// Load users from database on page load
function loadUsers() {
    fetch('get_users.php')
        .then(response => {
            if (!response.ok) throw new Error('Failed to load users');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                users = data.users;
                displayUsers(users);
            } else {
                console.error('Error loading users:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

// Display users in table
function displayUsers(usersToDisplay) {
    const tableBody = usersTable.querySelector('tbody');
    tableBody.innerHTML = '';

    if (usersToDisplay.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#999;">No users found</td></tr>';
        return;
    }

    usersToDisplay.forEach(user => {
        // support different column names by falling back
        const uid = user.id || user.user_id || user.ID || 0;
        const username = user.username || user.user_name || user.name || user.full_name || user.email || '';
        const lib = user.library_id || user.libraryId || user.lib_id || user.libraryID || '';
        const role = user.role || user.user_role || '';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${uid}</td>
            <td>${username}</td>
            <td>${lib}</td>
            <td>${role}</td>
            <td>
                <button class="btn btn-edit" onclick="editUser(${uid})">Edit</button>
                <button class="btn btn-delete" onclick="deleteUser(${uid})">Delete</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Open modal
addUserBtn.addEventListener('click', () => {
    addUserModal.classList.add('active');
});

// Close modal
modalClose.addEventListener('click', () => {
    addUserModal.classList.remove('active');
});

cancelBtn.addEventListener('click', () => {
    addUserModal.classList.remove('active');
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === addUserModal) {
        addUserModal.classList.remove('active');
    }
    if (e.target === editUserModal) {
        editUserModal.classList.remove('active');
    }
});

// Edit modal handlers
editModalClose.addEventListener('click', () => {
    editUserModal.classList.remove('active');
});

editCancelBtn.addEventListener('click', () => {
    editUserModal.classList.remove('active');
});

// Open edit modal
function editUser(userId) {
    const user = users.find(u => (u.id || u.user_id || u.ID) === userId);
    if (!user) {
        alert('User not found');
        return;
    }
    
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserPassword').value = '';
    editUserModal.classList.add('active');
}

// Handle edit form submission
editUserForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const userId = document.getElementById('editUserId').value;
    const password = document.getElementById('editUserPassword').value;
    
    // Only send password if it was changed
    const payload = { id: userId };
    if (password) payload.password = password;
    
    fetch('edit_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload users from database
            loadUsers();
            // Reset form and close modal
            editUserForm.reset();
            editUserModal.classList.remove('active');
            alert('User updated successfully');
        } else {
            alert('Error updating user: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update user');
    });
});

// Handle form submission
addUserForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const name = document.getElementById('userName').value;
    const libraryId = document.getElementById('userLibraryId').value;
    const role = document.getElementById('userRole').value;
    const password = document.getElementById('userPassword') ? document.getElementById('userPassword').value : null;

    // Send data to backend to add user
    const payload = { name, libraryId, role };
    if (password) payload.password = password;

    fetch('add_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
            if (data.success) {
            // Reload users from database
            loadUsers();
            // Reset form and close modal
                addUserForm.reset();
            addUserModal.classList.remove('active');
        } else {
            alert('Error adding user: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add user');
    });
});

// Delete user
function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    fetch('delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers();
        } else {
            alert('Error deleting user: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Search users
searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const filtered = users.filter(user =>
        ( (user.username || user.user_name || user.name || user.full_name || user.email || '') .toString().toLowerCase().includes(searchTerm) ) ||
        ( (user.library_id || user.libraryId || user.lib_id || user.libraryID || '') .toString().toLowerCase().includes(searchTerm) ) ||
        ( (user.role || user.user_role || '') .toString().toLowerCase().includes(searchTerm) )
    );
    displayUsers(filtered);
});

// Load users on page load
document.addEventListener('DOMContentLoaded', loadUsers);


