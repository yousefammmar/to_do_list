<?php
session_start();

// Protect page - only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'database.php';
$user_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task & Notes Manager</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <!-- React CDN -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>

<body>
    <div class="background-particles">
        <div class="particle p1"></div>
        <div class="particle p2"></div>
        <div class="particle p3"></div>
        <div class="particle p4"></div>
        <div class="particle p5"></div>
        <div class="particle p6"></div>
    </div>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <h1 style="margin: 0; flex: 1;">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <a href="about.php" class="btn btn-secondary" style="margin-left: auto; margin-right: 0.5rem;" aria-label="About our team">About Us</a>
        </div>
        <nav>
            <a href="index.php" class="btn btn-primary" aria-label="Go to homepage">Home</a>
            <a href="task_history.php" class="btn btn-info" aria-label="View task history">Task History</a>
            <a href="profile.php" class="btn btn-warning" aria-label="Go to profile settings">Profile</a>
            <a href="logout.php" class="btn btn-danger" aria-label="Logout from your account">Logout</a>
        </nav>
    </header>

    <main>
        <div class="container dashboard-container">
            <!-- Tasks Manager Section -->
            <section class="tasks-section">
                <h2>Tasks Manager</h2>
                <p>Manage your tasks. Only tasks that are not completed are shown here.</p>

                <!-- Add Task Form -->
                <form id="addTaskForm" class="task-form">
                    <input type="text" id="taskInput" placeholder="Enter task..." required aria-label="Enter new task">
                    <button type="submit" class="btn btn-primary" aria-label="Add new task">Add Task</button>
                </form>

                <!-- React TaskList Component will render here -->
                <div id="tasks-root"></div>
            </section>

            <!-- Notes Manager Section -->
            <section class="notes-section">
                <h2>Notes Manager</h2>
                <p>Add and manage your notes.</p>

                <!-- Add Note Form -->
                <form id="addNoteForm" class="note-form">
                    <input type="text" id="noteInput" placeholder="Write a short note..." required aria-label="Enter new note">
                    <button type="submit" class="btn btn-primary" aria-label="Add new note">Add Note</button>
                </form>

                <!-- Notes List -->
                <ul class="notes-list" id="notesList">
                    <?php
                    try {
                        $stmt = $conn->prepare("SELECT id, content, created_at FROM items WHERE user_id = :user_id AND type = 'note' ORDER BY created_at DESC");
                        $stmt->execute([':user_id' => $_SESSION['user_id']]);
                        $notes = $stmt->fetchAll();

                        if (empty($notes)) {
                            echo '<li class="placeholder">No notes yet.</li>';
                        } else {
                            foreach ($notes as $note) {
                                echo '<li data-note-id="' . htmlspecialchars($note['id']) . '">';
                                echo '<span>' . htmlspecialchars($note['content']) . '</span>';
                                echo '<button type="button" class="btn btn-danger btn-sm delete-note" data-id="' . htmlspecialchars($note['id']) . '" aria-label="Delete note">Delete</button>';
                                echo '</li>';
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<li class="error">Error loading notes.</li>';
                    }
                    ?>
                </ul>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> To-Do & Notes Manager. All rights reserved.</p>
    </footer>

    <script type="text/babel">
        const { useState, useEffect } = React;

        // TaskList React Component
        function TaskList() {
            const [tasks, setTasks] = useState([]);
            const [loading, setLoading] = useState(true);

            // Fetch tasks from PHP backend
            const fetchTasks = async () => {
                try {
                    const response = await fetch('fetch_tasks.php');
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Failed to fetch tasks:', response.status, errorText);
                        throw new Error('Failed to fetch tasks');
                    }
                    const data = await response.json();
                    
                    // Debug: log the data received
                    console.log('Tasks fetched:', data);
                    
                    // Check if data is an array
                    if (!Array.isArray(data)) {
                        console.error('Invalid data format:', data);
                        setTasks([]);
                        setLoading(false);
                        return;
                    }
                    
                    // Filter to show only NOT-Done tasks for Tasks Manager section
                    const notDoneTasks = data.filter(task => {
                        const status = (task.status || '').toLowerCase();
                        const isNotDone = status !== 'completed' && status !== 'done';
                        console.log('Task:', task.content, 'Status:', status, 'Show:', isNotDone);
                        return isNotDone;
                    });
                    
                    console.log('Filtered tasks:', notDoneTasks);
                    setTasks(notDoneTasks);
                    setLoading(false);
                } catch (error) {
                    console.error('Error fetching tasks:', error);
                    setTasks([]);
                    setLoading(false);
                }
            };

            useEffect(() => {
                fetchTasks();
            }, []);

            const handleStatusChange = async (taskId, newStatus) => {
                try {
                    const formData = new FormData();
                    formData.append('id', taskId);
                    formData.append('status', newStatus);
                    
                    const response = await fetch('update.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        fetchTasks(); // Refresh the list
                    }
                } catch (error) {
                    console.error('Error updating task status:', error);
                }
            };

            const handleDelete = async (taskId) => {
                if (!confirm('Are you sure you want to delete this task?')) {
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('id', taskId);
                    
                    const response = await fetch('delete.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        fetchTasks(); // Refresh the list
                    }
                } catch (error) {
                    console.error('Error deleting task:', error);
                }
            };

            const handleEdit = async (taskId, newContent) => {
                try {
                    const formData = new FormData();
                    formData.append('id', taskId);
                    formData.append('content', newContent);
                    
                    const response = await fetch('update.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        fetchTasks(); // Refresh the list
                    }
                } catch (error) {
                    console.error('Error updating task:', error);
                }
            };

            if (loading) {
                return <p>Loading tasks...</p>;
            }

            if (tasks.length === 0) {
                return <p className="placeholder">No tasks yet. Add a task to get started!</p>;
            }

            return (
                <ul className="task-list">
                    {tasks.map(task => (
                        <TaskItem 
                            key={task.id} 
                            task={task} 
                            onStatusChange={handleStatusChange}
                            onDelete={handleDelete}
                            onEdit={handleEdit}
                        />
                    ))}
                </ul>
            );
        }

        // TaskItem component
        function TaskItem({ task, onStatusChange, onDelete, onEdit }) {
            const [isEditing, setIsEditing] = useState(false);
            const [editValue, setEditValue] = useState(task.content);
            const currentStatus = task.status || 'pending';

            const handleSave = () => {
                onEdit(task.id, editValue);
                setIsEditing(false);
            };

            const getStatusBadge = (status) => {
                const statusMap = {
                    'pending': { text: 'Pending', class: 'status-pending' },
                    'in progress': { text: 'In Progress', class: 'status-in-progress' },
                    'in_progress': { text: 'In Progress', class: 'status-in-progress' },
                    'completed': { text: 'Done', class: 'status-completed' },
                    'done': { text: 'Done', class: 'status-completed' }
                };
                const statusInfo = statusMap[status] || statusMap['pending'];
                return <span className={`status-badge ${statusInfo.class}`}>{statusInfo.text}</span>;
            };

            const getNextStatus = (current) => {
                const status = (current || '').toLowerCase();
                if (status === 'pending' || status === '') {
                    return 'in_progress';
                } else if (status === 'in progress' || status === 'in_progress') {
                    return 'completed';
                }
                return current;
            };

            return (
                <li className="task-item">
                    {isEditing ? (
                        <div className="edit-form">
                            <input 
                                type="text" 
                                value={editValue} 
                                onChange={(e) => setEditValue(e.target.value)}
                                aria-label="Edit task"
                            />
                            <button onClick={handleSave} className="btn btn-success btn-sm">Save</button>
                            <button onClick={() => setIsEditing(false)} className="btn btn-secondary btn-sm">Cancel</button>
                        </div>
                    ) : (
                        <>
                            <div className="task-content">
                                <div className="task-text">
                                    <span>{task.content}</span>
                                </div>
                                {getStatusBadge(currentStatus)}
                            </div>
                            <div className="task-actions">
                                {currentStatus !== 'completed' && currentStatus !== 'done' && (
                                    <button 
                                        onClick={() => onStatusChange(task.id, getNextStatus(currentStatus))} 
                                        className="btn btn-info btn-sm"
                                        aria-label="Change task status"
                                    >
                                        {currentStatus === 'pending' || currentStatus === '' || !currentStatus ? 'Start' : 'Mark Done'}
                                    </button>
                                )}
                                <button 
                                    onClick={() => setIsEditing(true)} 
                                    className="btn btn-warning btn-sm"
                                    aria-label="Edit task"
                                >
                                    Edit
                                </button>
                                <button 
                                    onClick={() => onDelete(task.id)} 
                                    className="btn btn-danger btn-sm"
                                    aria-label="Delete task"
                                >
                                    Delete
                                </button>
                            </div>
                        </>
                    )}
                </li>
            );
        }

        // Render TaskList component
        const root = ReactDOM.createRoot(document.getElementById('tasks-root'));
        root.render(<TaskList />);
    </script>

    <script>
        // Handle add task form submission
        document.getElementById('addTaskForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const taskInput = document.getElementById('taskInput');
            const taskContent = taskInput.value.trim();

            if (taskContent === '') {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('type', 'task');
                formData.append('content', taskContent);

                const response = await fetch('save.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    taskInput.value = '';
                    // Reload the page to refresh React component
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error adding task:', error);
            }
        });

        // Handle add note form submission
        document.getElementById('addNoteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const noteInput = document.getElementById('noteInput');
            const noteContent = noteInput.value.trim();

            if (noteContent === '') {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('type', 'note');
                formData.append('content', noteContent);

                const response = await fetch('save.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    noteInput.value = '';
                    // Reload notes list
                    loadNotes();
                } else {
                    const errorMsg = result.error || 'Error adding note. Please try again.';
                    alert(errorMsg);
                    console.error('Error response:', result);
                }
            } catch (error) {
                console.error('Error adding note:', error);
                alert('Error adding note. Please try again.');
            }
        });

        // Function to load notes dynamically
        async function loadNotes() {
            try {
                const response = await fetch('fetch_notes.php');

                if (!response.ok) {
                    throw new Error('Failed to fetch notes');
                }

                const notes = await response.json();

                // Check if notes is an array
                if (!Array.isArray(notes)) {
                    console.error('Invalid notes data format:', notes);
                    return;
                }

                const notesList = document.getElementById('notesList');

                if (notes.length === 0) {
                    notesList.innerHTML = '<li class="placeholder">No notes yet.</li>';
                } else {
                    notesList.innerHTML = notes.map(note => `
                        <li data-note-id="${escapeHtml(String(note.id))}">
                            <span>${escapeHtml(note.content)}</span>
                            <button type="button" class="btn btn-danger btn-sm delete-note" data-id="${escapeHtml(String(note.id))}" aria-label="Delete note">Delete</button>
                        </li>
                    `).join('');

                    // Re-attach delete event listeners
                    attachDeleteListeners();
                }
            } catch (error) {
                console.error('Error loading notes:', error);
                const notesList = document.getElementById('notesList');
                if (notesList) {
                    notesList.innerHTML = '<li class="error">Error loading notes. Please refresh the page.</li>';
                }
            }
        }

        // Function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Function to attach delete listeners
        function attachDeleteListeners() {
            document.querySelectorAll('.delete-note').forEach(button => {
                button.addEventListener('click', async function() {
                    const noteId = this.getAttribute('data-id');

                    if (!confirm('Are you sure you want to delete this note?')) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('id', noteId);

                        const response = await fetch('delete.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (response.ok) {
                            // Remove the note from the list
                            const noteItem = document.querySelector(`li[data-note-id="${noteId}"]`);
                            if (noteItem) {
                                noteItem.remove();
                            }

                            // If no notes left, show placeholder
                            const notesList = document.getElementById('notesList');
                            if (notesList.children.length === 0) {
                                notesList.innerHTML = '<li class="placeholder">No notes yet.</li>';
                            }
                        } else {
                            alert('Error deleting note. Please try again.');
                        }
                    } catch (error) {
                        console.error('Error deleting note:', error);
                        alert('Error deleting note. Please try again.');
                    }
                });
            });
        }

        // Attach delete listeners on page load
        document.addEventListener('DOMContentLoaded', function() {
            attachDeleteListeners();
        });
    </script>
</body>

</html>