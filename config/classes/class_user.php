<?php

require_once 'config/dbinfo.php';

class User {
  private $db;

  function __construct() {
    // Establish database connection
    try {
      $this->db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      die("Failed to connect to database: " . $e->getMessage());
    }
  }

  function login($username, $password) {
    // Check if user exists in the database
    $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  
    if ($user) {
      // Verify password
      if (password_verify($password, $user['password'])) {
        // Password is correct, start a new session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
  
        // Log session in database
        $stmt = $this->db->prepare("INSERT INTO sessions (user_id, session_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], session_id()]);
  
        // Redirect to myaccount.php
        header('Location: myaccount.php');
        exit;
      } else {
        // Password is incorrect
        return false;
      }
    } else {
      // User not found
      return false;
    }
  }
  
  function logout() {
    // Unset all session variables
    $_SESSION = array();

    // Delete session from database
    $session_id = session_id();
    $stmt = $this->db->prepare('DELETE FROM sessions WHERE session_id = ?');
    $stmt->execute([$session_id]);

    // Destroy session cookie
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
          $params['path'], $params['domain'],
          $params['secure'], $params['httponly']
      );
    }


    // Destroy the session
    session_destroy();
}

  function isLoggedIn() {
    // Check if user is logged in
    return isset($_SESSION['user_id']);
  }

}

?>
