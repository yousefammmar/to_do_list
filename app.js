import { auth, db, storage } from './firebase-config.js';
import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signOut,
    onAuthStateChanged,
    updateProfile
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
import {
    collection,
    addDoc,
    onSnapshot,
    query,
    where,
    orderBy,
    deleteDoc,
    doc,
    updateDoc,
    serverTimestamp,
    setDoc,
    getDoc
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
import {
    ref,
    uploadBytes,
    getDownloadURL
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-storage.js";

// DOM Elements
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const logoutBtns = document.querySelectorAll('[id^="logout-btn"]');
const addTaskForm = document.getElementById('addTaskForm');
const addNoteForm = document.getElementById('addNoteForm');
const taskListEl = document.getElementById('taskList');
const notesListEl = document.getElementById('notesList');
const completedTasksListEl = document.getElementById('completedTasksList');
const userNameDisplay = document.getElementById('user-name-display');
const authErrorMsg = document.getElementById('auth-error-message');
const loadingIndicator = document.getElementById('loading-indicator');
const profileImageForm = document.getElementById('imageUploadForm');
const profileForm = document.getElementById('profileForm');

// Auth State Observer
onAuthStateChanged(auth, async (user) => {
    if (user) {
        // User is signed in
        console.log("User signed in:", user.uid);
        updateUIForAuth(true);
        updateUserNameDisplay(user);

        // Setup listeners based on current page
        setupDashboardListeners(user);
        setupTaskHistoryListeners(user);
        setupProfileListeners(user);

        // Redirect from auth pages
        if (window.location.pathname.includes('login.html') || window.location.pathname.includes('register.html')) {
            window.location.href = 'dashboard.html';
        }

    } else {
        // User is signed out
        console.log("User signed out");
        updateUIForAuth(false);

        // Redirect protected pages
        if (window.location.pathname.includes('dashboard.html') ||
            window.location.pathname.includes('profile.html') ||
            window.location.pathname.includes('task_history.html')) {
            window.location.href = 'login.html';
        }
    }
});

function updateUserNameDisplay(user) {
    if (userNameDisplay) {
        userNameDisplay.textContent = user.displayName || user.email.split('@')[0];
    }
}

// Update UI based on auth state
function updateUIForAuth(isLoggedIn) {
    const loggedInEls = document.querySelectorAll('.auth-logged-in');
    const loggedOutEls = document.querySelectorAll('.auth-logged-out');

    loggedInEls.forEach(el => el.style.display = isLoggedIn ? 'inline-flex' : 'none');
    loggedOutEls.forEach(el => el.style.display = isLoggedIn ? 'none' : 'inline-flex');
}

// Login Logic
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        showAuthError('');
        loadingIndicator.style.display = 'block';

        try {
            await signInWithEmailAndPassword(auth, email, password);
        } catch (error) {
            console.error("Login error:", error);
            showAuthError(getErrorMessage(error));
            loadingIndicator.style.display = 'none';
        }
    });
}

// Register Logic
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        showAuthError('');
        loadingIndicator.style.display = 'block';

        try {
            const userCredential = await createUserWithEmailAndPassword(auth, email, password);
            const user = userCredential.user;

            // Update Auth Profile
            await updateProfile(user, {
                displayName: name
            });

            // Create User Document in Firestore
            await setDoc(doc(db, "users", user.uid), {
                name: name,
                email: email,
                createdAt: serverTimestamp()
            });

        } catch (error) {
            console.error("Registration error:", error);
            showAuthError(getErrorMessage(error));
            loadingIndicator.style.display = 'none';
        }
    });
}

// Logout Logic
logoutBtns.forEach(btn => {
    btn.addEventListener('click', async () => {
        try {
            await signOut(auth);
            window.location.href = 'index.html';
        } catch (error) {
            console.error("Logout error:", error);
        }
    });
});

function showAuthError(msg) {
    if (authErrorMsg) {
        authErrorMsg.textContent = msg;
        authErrorMsg.style.display = msg ? 'block' : 'none';
    }
}

function getErrorMessage(error) {
    switch (error.code) {
        case 'auth/invalid-email': return 'Invalid email address.';
        case 'auth/user-disabled': return 'User account is disabled.';
        case 'auth/user-not-found': return 'No user found with this email.';
        case 'auth/wrong-password': return 'Incorrect password.';
        case 'auth/email-already-in-use': return 'Email is already in use.';
        case 'auth/weak-password': return 'Password is too weak.';
        default: return error.message;
    }
}

// Dashboard Listeners
function setupDashboardListeners(user) {
    if (!taskListEl && !notesListEl) return;

    // Tasks
    if (taskListEl) {
        const q = query(
            collection(db, "items"),
            where("userId", "==", user.uid),
            where("type", "==", "task"),
            orderBy("createdAt", "desc")
        );

        onSnapshot(q, (snapshot) => {
            const tasks = [];
            snapshot.forEach((doc) => {
                tasks.push({ id: doc.id, ...doc.data() });
            });
            renderTasks(tasks);
        }, (error) => {
            console.error("Error tasks:", error);
            taskListEl.innerHTML = '<li class="placeholder error">Error loading tasks.</li>';
        });
    }

    // Notes
    if (notesListEl) {
        const q = query(
            collection(db, "items"),
            where("userId", "==", user.uid),
            where("type", "==", "note"),
            orderBy("createdAt", "desc")
        );

        onSnapshot(q, (snapshot) => {
            const notes = [];
            snapshot.forEach((doc) => {
                notes.push({ id: doc.id, ...doc.data() });
            });
            renderNotes(notes);
        }, (error) => {
            console.error("Error notes:", error);
            notesListEl.innerHTML = '<li class="placeholder error">Error loading notes.</li>';
        });
    }
}

// Task History Listener
function setupTaskHistoryListeners(user) {
    if (!completedTasksListEl) return;

    const q = query(
        collection(db, "items"),
        where("userId", "==", user.uid),
        where("type", "==", "task"),
        where("status", "in", ["completed", "done"]),
        orderBy("createdAt", "desc")
    );

    onSnapshot(q, (snapshot) => {
        const tasks = [];
        snapshot.forEach((doc) => {
            tasks.push({ id: doc.id, ...doc.data() });
        });
        renderCompletedTasks(tasks);
    }, (error) => {
        // If index is missing (common with compound queries), log it
        if (error.code === 'failed-precondition') {
            completedTasksListEl.innerHTML = '<li class="placeholder error">Index required. Check console for link to create index.</li>';
        } else {
            completedTasksListEl.innerHTML = '<li class="placeholder error">Error loading completed tasks.</li>';
        }
        console.error("Error history:", error);
    });
}

// Profile Listeners
async function setupProfileListeners(user) {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const profileImagePreview = document.getElementById('profileImagePreview');
    const profileImagePlaceholder = document.getElementById('profileImagePlaceholder');

    if (!nameInput) return; // Not on profile page

    // Load User Data
    if (user.displayName) nameInput.value = user.displayName;
    if (user.email) emailInput.value = user.email;

    if (user.photoURL) {
        profileImagePreview.src = user.photoURL;
        profileImagePreview.style.display = 'block';
        if (profileImagePlaceholder) profileImagePlaceholder.style.display = 'none';
    }

    // Handle Profile Update
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newName = nameInput.value.trim();
            if (!newName) return;

            try {
                await updateProfile(user, { displayName: newName });
                await updateDoc(doc(db, "users", user.uid), { name: newName }, { merge: true });
                showMessage('profile-success-msg', 'Profile updated successfully!');
                updateUserNameDisplay(user);
            } catch (error) {
                console.error("Update profile error:", error);
                showMessage('profile-error-msg', 'Failed to update profile.');
            }
        });
    }

    // Handle Image Upload
    if (profileImageForm) {
        profileImageForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('profileImageInput');
            const file = fileInput.files[0];
            if (!file) return;

            try {
                const storageRef = ref(storage, `profile_images/${user.uid}/${file.name}`);
                await uploadBytes(storageRef, file);
                const downloadURL = await getDownloadURL(storageRef);

                await updateProfile(user, { photoURL: downloadURL });
                await updateDoc(doc(db, "users", user.uid), { profile_image: downloadURL }, { merge: true });

                profileImagePreview.src = downloadURL;
                profileImagePreview.style.display = 'block';
                if (profileImagePlaceholder) profileImagePlaceholder.style.display = 'none';

                showMessage('profile-success-msg', 'Profile image updated successfully!');
            } catch (error) {
                console.error("Upload error:", error);
                showMessage('profile-error-msg', 'Failed to upload image. ' + error.message);
            }
        });
    }
}

function showMessage(elId, msg) {
    const el = document.getElementById(elId);
    if (el) {
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 3000);
    }
}

// Add Item Logic
if (addTaskForm) {
    addTaskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('taskInput');
        const content = input.value.trim();
        if (!content) return;

        try {
            await addDoc(collection(db, "items"), {
                userId: auth.currentUser.uid,
                type: 'task',
                content: content,
                status: 'pending',
                createdAt: serverTimestamp()
            });
            input.value = '';
        } catch (error) {
            console.error("Add task error:", error);
        }
    });
}

if (addNoteForm) {
    addNoteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('noteInput');
        const content = input.value.trim();
        if (!content) return;

        try {
            await addDoc(collection(db, "items"), {
                userId: auth.currentUser.uid,
                type: 'note',
                content: content,
                createdAt: serverTimestamp()
            });
            input.value = '';
        } catch (error) {
            console.error("Add note error:", error);
        }
    });
}

// Render Functions
function renderTasks(tasks) {
    const activeTasks = tasks.filter(t => t.status !== 'completed' && t.status !== 'done');
    if (activeTasks.length === 0) {
        taskListEl.innerHTML = '<li class="placeholder">No tasks yet. Add a task to get started!</li>';
        return;
    }

    taskListEl.innerHTML = activeTasks.map(task => `
        <li class="task-item" id="task-${task.id}">
            <div class="task-content">
                <div class="task-text">
                    <span>${escapeHtml(task.content)}</span>
                </div>
                <span class="status-badge status-${(task.status || 'pending').replace(' ', '-')}">${task.status || 'Pending'}</span>
            </div>
            <div class="task-actions">
                <button onclick="window.updateTaskStatus('${task.id}', '${getNextStatus(task.status)}')" class="btn btn-info btn-sm">
                    ${!task.status || task.status === 'pending' ? 'Start' : 'Mark Done'}
                </button>
                <button onclick="window.deleteItem('${task.id}', 'task')" class="btn btn-danger btn-sm">Delete</button>
            </div>
        </li>
    `).join('');
}

function renderNotes(notes) {
    if (notes.length === 0) {
        notesListEl.innerHTML = '<li class="placeholder">No notes yet.</li>';
        return;
    }
    notesListEl.innerHTML = notes.map(note => `
        <li data-note-id="${note.id}">
            <span>${escapeHtml(note.content)}</span>
            <button onclick="window.deleteItem('${note.id}', 'note')" class="btn btn-danger btn-sm delete-note">Delete</button>
        </li>
    `).join('');
}

function renderCompletedTasks(tasks) {
    if (tasks.length === 0) {
        completedTasksListEl.innerHTML = '<li class="placeholder">No completed tasks yet.</li>';
        return;
    }
    completedTasksListEl.innerHTML = tasks.map(task => {
        const date = task.createdAt && task.createdAt.toDate ? task.createdAt.toDate().toLocaleDateString() : 'Just now';
        return `
        <li class="task-item completed-task">
            <div class="task-content">
                <div class="task-text">
                    <span>${escapeHtml(task.content)}</span>
                    <span class="task-date">Completed on: ${date}</span>
                </div>
                <span class="status-badge status-completed">Completed</span>
            </div>
        </li>
        `;
    }).join('');
}

// Global actions
window.updateTaskStatus = async (id, newStatus) => {
    try {
        await updateDoc(doc(db, "items", id), { status: newStatus });
    } catch (error) {
        console.error("Update error:", error);
    }
};

window.deleteItem = async (id, type) => {
    if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
    try {
        await deleteDoc(doc(db, "items", id));
    } catch (error) {
        console.error("Delete error:", error);
    }
};

function getNextStatus(current) {
    const status = (current || '').toLowerCase();
    if (status === 'pending' || !status) return 'in_progress';
    if (status === 'in_progress' || status === 'in progress') return 'completed';
    return current;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
