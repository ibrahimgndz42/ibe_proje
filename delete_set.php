<?php
include "session_check.php";
include "connectDB.php";

if (isset($_GET['id'])) {
    $set_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Sadece seti oluşturan silebilir
    $sql_check = "SELECT user_id FROM sets WHERE set_id = $set_id";
    $result = $conn->query($sql_check);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['user_id'] == $user_id) {
            // Önce kartları sil
            $conn->query("DELETE FROM cards WHERE set_id = $set_id");
            // Sonra seti sil
            $conn->query("DELETE FROM sets WHERE set_id = $set_id");
            
            header("Location: sets.php?msg=deleted");
            exit;
        } else {
            echo "Bu seti silme yetkiniz yok.";
            exit; 
        }
    } else {
        echo "Set bulunamadı.";
        exit;
    }
} else {
    echo "ID gerekli.";
    exit;
}
?>
