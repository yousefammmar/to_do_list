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
    setDoc
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
try {
    onAuthStateChanged(auth, async (user) => {
        const isGuest = localStorage.getItem('guest_mode') === 'true';

        if (user || isGuest) {
            console.log(isGuest ? "Guest access active" : "User signed in:", user?.uid);
            updateUIForAuth(true);

            if (user) {
                updateUserNameDisplay(user);
                setupDashboardListeners(user);
                setupTaskHistoryListeners(user);
                setupProfileListeners(user);
            } else {
                if (userNameDisplay) userNameDisplay.textContent = "Guest User (Preview)";
            }

            // Redirect from auth pages
            if (window.location.pathname.endsWith('login.html') || window.location.pathname.endsWith('register.html') || window.location.pathname === '/' || window.location.pathname.endsWith('index.html')) {
                // If on home/auth pages and logged in/guest, go to dashboard
                if (!window.location.pathname.includes('dashboard.html')) {
                    window.location.href = 'dashboard.html';
                }
            }
        } else {
            console.log("User signed out");
            updateUIForAuth(false);

            // Redirect protected pages
            const protectedPages = ['dashboard.html', 'profile.html', 'task_history.html'];
            const isProtected = protectedPages.some(page => window.location.pathname.endsWith(page));

            if (isProtected) {
                window.location.href = 'login.html';
            }
        }
    });
} catch (error) {
    console.error("Firebase init error:", error);
    // Even if Firebase fails, allow guest mode check
    if (localStorage.getItem('guest_mode') === 'true') {
        updateUIForAuth(true);
        if (userNameDisplay) userNameDisplay.textContent = "Guest User (Preview)";
    }
}

// Global Guest Login helper
window.enterGuestMode = () => {
    localStorage.setItem('guest_mode', 'true');
    window.location.href = 'dashboard.html';
};

function updateUserNameDisplay(user) {
    if (userNameDisplay) {
        userNameDisplay.textContent = user.displayName || user.email.split('@')[0];
    }
}

function updateUIForAuth(isLoggedIn) {
    const loggedInEls = document.querySelectorAll('.auth-logged-in');
    const loggedOutEls = document.querySelectorAll('.auth-logged-out');
    loggedInEls.forEach(el => el.style.display = isLoggedIn ? 'inline-flex' : 'none');
    loggedOutEls.forEach(el => el.style.display = isLoggedIn ? 'none' : 'inline-flex');
}

// Auth Logic
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        showAuthError('');
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        try {
            await signInWithEmailAndPassword(auth, email, password);
        } catch (error) {
            showAuthError(getErrorMessage(error));
            if (loadingIndicator) loadingIndicator.style.display = 'none';
        }
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        showAuthError('');
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        try {
            const userCredential = await createUserWithEmailAndPassword(auth, email, password);
            await updateProfile(userCredential.user, { displayName: name });
            await setDoc(doc(db, "users", userCredential.user.uid), { name, email, createdAt: serverTimestamp() });
        } catch (error) {
            showAuthError(getErrorMessage(error));
            if (loadingIndicator) loadingIndicator.style.display = 'none';
        }
    });
}

logoutBtns.forEach(btn => {
    btn.addEventListener('click', async () => {
        localStorage.removeItem('guest_mode');
        try {
            await signOut(auth);
        } catch (e) {
            console.log("Sign out (guest or error):", e.message);
        }
        window.location.href = 'index.html';
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
        case 'auth/invalid-email': return 'Invalid email.';
        case 'auth/user-not-found': return 'User not found.';
        case 'auth/wrong-password': return 'Wrong password.';
        default: return error.message;
    }
}

// Data Listeners
function setupDashboardListeners(user) {
    if (taskListEl) {
        const q = query(collection(db, "items"), where("userId", "==", user.uid), where("type", "==", "task"), orderBy("createdAt", "desc"));
        onSnapshot(q, (snapshot) => {
            const tasks = [];
            snapshot.forEach(doc => tasks.push({ id: doc.id, ...doc.data() }));
            renderTasks(tasks);
        });
    }
    if (notesListEl) {
        const q = query(collection(db, "items"), where("userId", "==", user.uid), where("type", "==", "note"), orderBy("createdAt", "desc"));
        onSnapshot(q, (snapshot) => {
            const notes = [];
            snapshot.forEach(doc => notes.push({ id: doc.id, ...doc.data() }));
            renderNotes(notes);
        });
    }
}

function setupTaskHistoryListeners(user) {
    if (!completedTasksListEl) return;
    const q = query(collection(db, "items"), where("userId", "==", user.uid), where("type", "==", "task"), where("status", "==", "completed"), orderBy("createdAt", "desc"));
    onSnapshot(q, (snapshot) => {
        const tasks = [];
        snapshot.forEach(doc => tasks.push({ id: doc.id, ...doc.data() }));
        renderCompletedTasks(tasks);
    });
}

async function setupProfileListeners(user) {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    if (!nameInput) return;
    nameInput.value = user.displayName || '';
    emailInput.value = user.email || '';

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await updateProfile(user, { displayName: nameInput.value });
            await updateDoc(doc(db, "users", user.uid), { name: nameInput.value }, { merge: true });
            alert("Profile updated!");
        });
    }
}

if (addTaskForm) {
    addTaskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const content = document.getElementById('taskInput').value;
        await addDoc(collection(db, "items"), { userId: auth.currentUser.uid, type: 'task', content, status: 'pending', createdAt: serverTimestamp() });
        document.getElementById('taskInput').value = '';
    });
}

if (addNoteForm) {
    addNoteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const content = document.getElementById('noteInput').value;
        await addDoc(collection(db, "items"), { userId: auth.currentUser.uid, type: 'note', content, createdAt: serverTimestamp() });
        document.getElementById('noteInput').value = '';
    });
}

function renderTasks(tasks) {
    const active = tasks.filter(t => t.status !== 'completed');
    taskListEl.innerHTML = active.length ? active.map(t => `
        <li class="task-item">
            <div class="task-content"><span>${t.content}</span><span class="status-badge">${t.status}</span></div>
            <div class="task-actions">
                <button onclick="window.updateStatus('${t.id}', 'completed')" class="btn btn-sm btn-info">Done</button>
                <button onclick="window.deleteItem('${t.id}')" class="btn btn-sm btn-danger">Delete</button>
            </div>
        </li>
    `).join('') : '<li class="placeholder">No tasks.</li>';
}

function renderNotes(notes) {
    notesListEl.innerHTML = notes.length ? notes.map(n => `
        <li><span>${n.content}</span><button onclick="window.deleteItem('${n.id}')" class="btn btn-sm btn-danger">Delete</button></li>
    `).join('') : '<li class="placeholder">No notes.</li>';
}

function renderCompletedTasks(tasks) {
    completedTasksListEl.innerHTML = tasks.length ? tasks.map(t => `
        <li class="task-item"><span>${t.content}</span><span class="status-badge status-completed">Completed</span></li>
    `).join('') : '<li class="placeholder">No history.</li>';
}

window.updateStatus = async (id, status) => await updateDoc(doc(db, "items", id), { status });
window.deleteItem = async (id) => { if (confirm("Delete?")) await deleteDoc(doc(db, "items", id)); };
