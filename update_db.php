<?php
include "connectDB.php";

// 1. Add 'category' column to 'sets' table
$sql_check = "SHOW COLUMNS FROM sets LIKE 'category'";
$result = $conn->query($sql_check);

if ($result->num_rows == 0) {
    $sql_alter = "ALTER TABLE sets ADD COLUMN category VARCHAR(50) DEFAULT 'Genel' AFTER description";
    if ($conn->query($sql_alter) === TRUE) {
        echo "Added 'category' column to 'sets' table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "'category' column already exists.<br>";
}

// 2. Create 'folders' table
$sql_folder = "CREATE TABLE IF NOT EXISTS folders (
    folder_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql_folder) === TRUE) {
    echo "'folders' table created or already exists.<br>";
} else {
    echo "Error creating 'folders' table: " . $conn->error . "<br>";
}

// 3. Create 'folder_sets' table
$sql_folder_sets = "CREATE TABLE IF NOT EXISTS folder_sets (
    folder_id INT(11) NOT NULL,
    set_id INT(11) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (folder_id, set_id),
    FOREIGN KEY (folder_id) REFERENCES folders(folder_id) ON DELETE CASCADE,
    FOREIGN KEY (set_id) REFERENCES sets(set_id) ON DELETE CASCADE
)";

if ($conn->query($sql_folder_sets) === TRUE) {
    echo "'folder_sets' table created or already exists.<br>";
} else {
    echo "Error creating 'folder_sets' table: " . $conn->error . "<br>";
}

// 4. Drop 'favorites' table if it exists (Optional clean up)
$sql_drop_fav = "DROP TABLE IF EXISTS favorites";
// 5. Create 'comments' table
$sql_comments = "CREATE TABLE IF NOT EXISTS comments (
    comment_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    set_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (set_id) REFERENCES sets(set_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql_comments) === TRUE) {
    echo "'comments' table created or already exists.<br>";
} else {
    echo "Error creating 'comments' table: " . $conn->error . "<br>";
}
?>
