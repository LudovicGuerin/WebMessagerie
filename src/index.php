<?php
header("Content-Type: application/json");

// Configuration de la base de données
$dsn = 'mysql:host=localhost;dbname=Messagerie';
$username = 'root';
$password = '';

try {
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(array("status" => "error", "message" => "Database connection failed: " . $e->getMessage()));
    exit();
}

// Fonctions pour les utilisateurs
function getUserById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE idUtilisateur = :id");
    $stmt->execute(array(':id' => $id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllUsers($db) {
    $stmt = $db->prepare("SELECT * FROM utilisateurs");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonctions pour les messages
function getMessageById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE idMessage = :id");
    $stmt->execute(array(':id' => $id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllMessages($db) {
    $stmt = $db->prepare("SELECT * FROM messages");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchMessagesBySender($db, $sender) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE NomExpediteur LIKE :sender");
    $stmt->execute(array(':sender' => '%' . $sender . '%'));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchMessagesByRecipient($db, $recipient) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE NomDestinataire LIKE :recipient");
    $stmt->execute(array(':recipient' => '%' . $recipient . '%'));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addMessage($db, $expediteur, $destinataire, $sujet, $contenu) {
    $stmt = $db->prepare("INSERT INTO messages (NomExpediteur, NomDestinataire, Sujet, Contenu) VALUES (:expediteur, :destinataire, :sujet, :contenu)");
    $stmt->execute(array(':expediteur' => $expediteur, ':destinataire' => $destinataire, ':sujet' => $sujet, ':contenu' => $contenu));
    return $db->lastInsertId();
}

// Traitement des requêtes
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['idUtilisateur'])) {
        // Obtenir un utilisateur par ID
        $id = intval($_GET['idUtilisateur']);
        $user = getUserById($db, $id);
        if ($user) {
            $response['status'] = 'success';
            $response['data'] = $user;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'User not found';
        }
    } elseif (isset($_GET['idMessage'])) {
        // Obtenir un message par ID
        $id = intval($_GET['idMessage']);
        $message = getMessageById($db, $id);
        if ($message) {
            $response['status'] = 'success';
            $response['data'] = $message;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Message not found';
        }
    } elseif (isset($_GET['NomExpediteur'])) {
        // Rechercher des messages par expéditeur
        $expediteur = $_GET['NomExpediteur'];
        $messages = searchMessagesBySender($db, $expediteur);
        if ($messages) {
            $response['status'] = 'success';
            $response['data'] = $messages;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'No messages found';
        }
    } elseif (isset($_GET['NomDestinataire'])) {
        // Rechercher des messages par destinataire
        $destinataire = $_GET['NomDestinataire'];
        $messages = searchMessagesByRecipient($db, $destinataire);
        if ($messages) {
            $response['status'] = 'success';
            $response['data'] = $messages;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'No messages found';
        }
    } else {
        // Obtenir tous les utilisateurs ou tous les messages
        if (isset($_GET['type']) && $_GET['type'] == 'messages') {
            $messages = getAllMessages($db);
            $response['status'] = 'success';
            $response['data'] = $messages;
        } else {
            $users = getAllUsers($db);
            $response['status'] = 'success';
            $response['data'] = $users;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input['NomExpediteur']) && isset($input['NomDestinataire']) && isset($input['Sujet']) && isset($input['Contenu'])) {
        $expediteur = $input['NomExpediteur'];
        $destinataire = $input['NomDestinataire'];
        $sujet = $input['Sujet'];
        $contenu = $input['Contenu'];
        $id = addMessage($db, $expediteur, $destinataire, $sujet, $contenu);
        $response['status'] = 'success';
        $response['message'] = 'Message added successfully';
        $response['idMessage'] = $id;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Missing parameters';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
