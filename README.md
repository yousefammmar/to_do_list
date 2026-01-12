# To-Do & Notes Manager (GitHub Ready Version)

This is a static version of the To-Do & Notes Manager, ready for hosting on GitHub Pages or any static site provider. It uses Firebase for authentication and data storage, allowing you to access your data from any device (laptop, phone, etc.).

## Setup Instructions

### 1. Create a Firebase Project
1. Go to [Firebase Console](https://console.firebase.google.com/).
2. Click **Add project** and follow the setup.
3. Once created, go to **Build** > **Authentication**.
   - Click **Get Started**.
   - Enable **Email/Password** provider.
4. Go to **Build** > **Firestore Database**.
   - Click **Create Database**.
   - Start in **Test mode** (for development) or **Production mode** (you'll need to configure rules).
   - Choose a location close to you.
   - Go to **Rules** tab and allow read/write:
     ```
     rules_version = '2';
     service cloud.firestore {
       match /databases/{database}/documents {
         match /{document=**} {
           allow read, write: if request.auth != null;
         }
       }
     }
     ```
     (Note: This rule allows any logged-in user to read/write any data. For better security, restrict to `request.auth.uid == resource.data.userId` for creating/updating.)
5. Go to **Build** > **Storage**.
   - Click **Get Started**.
   - Start in **Test mode** or **Production mode**.
   - Configure Rules similarly (allow read/write for authenticated users).

### 2. Get Configuration
1. In Firebase Console, click the **Gear icon** > **Project settings**.
2. Scroll down to **Your apps**.
3. Click the **Web** icon (`</>`).
4. Register app (e.g., "Todo App").
5. Copy the `firebaseConfig` object provided.

### 3. Update Configuration
1. Open `firebase-config.js` in this folder.
2. Replace the placeholder values in `const firebaseConfig = { ... }` with your actual configuration from step 2.

### 4. Deploy to GitHub
1. Create a new repository on GitHub.
2. Open terminal in this folder (`github_ready_version`).
3. Initialize Git:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```
4. Push to GitHub:
   ```bash
   git branch -M main
   git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
   git push -u origin main
   ```
5. Go to your GitHub repository > **Settings** > **Pages**.
6. Select `main` branch and `/ (root)` folder.
7. Save. Your site will be live at `https://YOUR_USERNAME.github.io/YOUR_REPO_NAME/`.

## Features
- **Cross-Device Sync**: Log in on any device to see your tasks and notes.
- **Tasks**: Add, edit, delete, and mark tasks as done.
- **Notes**: Add and delete sticky notes.
- **Profile**: Update your name and profile picture.
- **Task History**: View your completed tasks.

## Technologies
- HTML5, CSS3
- Vanilla JavaScript (ES6 Modules)
- Firebase Auth, Firestore, Storage
