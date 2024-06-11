<?php
header("Content-Type: application/json");

// ----------------------------------------------------------------------- Base de données ----------------------------------------------------------------------

// Configuration de la base de données
$dsn = 'mysql:host=localhost;dbname=id22243834_messagerie';
$username = 'id22243834_root';
$password = 'Me$$agerie2024';


//tentative de connexion à la base de donnée
try {
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(array("status" => "error", "message" => "Database connection failed: " . $e->getMessage()));
    exit();
}

// ------------------------------------------------------------------ Fonctions pour les utilisateurs -----------------------------------------------------------
//Récupération d'un utilisateur grâce à son id
function getUserById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE idUtilisateur = :id");
    $stmt->execute(array(':id' => $id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//Récupération de tous les utilisateurs
function getAllUsers($db) {
    $stmt = $db->prepare("SELECT NomUtilisateur FROM utilisateurs");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Ajout d'un nouvel utilisateur si le nom d'utilisateur est disponible
function addUser($db, $username, $password) {
    //on vérifie si le username existe déjà
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE NomUtilisateur = :username");
    $stmt->execute(array(':username' => $username));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result == false)
    {
        $stmt = $db->prepare("INSERT INTO utilisateurs (NomUtilisateur, MotDePasse) VALUES (:username, :mdp)");
        $stmt->execute(array(':username' => $username, ':mdp' => $password));
        return $db->lastInsertId();
    }
    else
    {
        return false;
    }
}

//Vérifie un couple nom d'utilisateur/mot de passe
function checkUser($db, $username, $password) {
    $connected = false;
    $stmt = $db->prepare("SELECT MotDePasse FROM utilisateurs WHERE NomUtilisateur = :username");
    $stmt->execute(array(':username' => $username));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result != false){
        if($result['MotDePasse'] == $password)
        {
            $connected = true;
        }
    }

    return $connected;
}

// ------------------------------------------------------------------------- Fonctions pour les messages ----------------------------------------------------------------

//Récupère un message grâce à son id
function getMessageById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE idMessage = :id");
    $stmt->execute(array(':id' => $id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//Récupère tous les messages
function getAllMessages($db) {
    $stmt = $db->prepare("SELECT * FROM messages");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Récupération des messages envoyés grâce au nom d'expéditeur
function searchMessagesBySender($db, $sender) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE NomExpediteur = :sender");
    $stmt->execute(array(':sender' => $sender));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Récupération des messages reçus grâce au nom du destinataire
function searchMessagesByRecipient($db, $recipient) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE NomDestinataire = :recipient");
    $stmt->execute(array(':recipient' => $recipient));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Ajout d'un nouveau message
function addMessage($db, $expediteur, $destinataire, $sujet, $contenu) {
    $stmt = $db->prepare("INSERT INTO messages (NomExpediteur, NomDestinataire, Sujet, Contenu) VALUES (:expediteur, :destinataire, :sujet, :contenu)");
    $stmt->execute(array(':expediteur' => $expediteur, ':destinataire' => $destinataire, ':sujet' => $sujet, ':contenu' => $contenu));
    return $db->lastInsertId();
}

// ------------------------------------------------------------------------------- Traitement des requêtes -----------------------------------------------------------------------------
$response = array();

try{
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
        }
        //connexion
        elseif(isset($_GET['NomUtilisateur']) && isset($_GET['MotDePasse'])) {
            $NomUtilisateur = $_GET['NomUtilisateur'];
            $MotDePasse = $_GET['MotDePasse'];
            $result = checkUser($db, $NomUtilisateur, $MotDePasse);

            if($result)
            {
                $response['status'] = 'success';
                $response['message'] = 'Connection was successful';
            }
            else
            {
                $response['status'] = 'failed';
                $response['message'] = 'Connection has failed : Wrong username or password';
            }
        } elseif(isset($_GET['GetAllUsers'])) { 
            // Rechercher tous les utilisateurs
            $users = getAllUsers($db);
            if ($users) {
                $response['status'] = 'success';
                $response['data'] = $users;
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Something went wrong...';
            }
        } else {
            // Pas de requête, on retourne une erreur
            $response['status'] = 'failed';
            $response['message'] = 'No request found';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        //création d'un nouveau message
        if (isset($input['NomExpediteur']) && isset($input['NomDestinataire']) && isset($input['Sujet']) && isset($input['Contenu'])) {
            $expediteur = $input['NomExpediteur'];
            $destinataire = $input['NomDestinataire'];
            $sujet = $input['Sujet'];
            $contenu = $input['Contenu'];
            $id = addMessage($db, $expediteur, $destinataire, $sujet, $contenu);
            $response['status'] = 'success';
            $response['message'] = 'Message added successfully';
            $response['idMessage'] = $id;
        }
        //Création d'un nouvel utilisateur
        elseif(isset($input['NomUtilisateur']) && isset($input['MotDePasse'])) {
            $NomUtilisateur = $input['NomUtilisateur'];
            $MotDePasse = $input['MotDePasse'];
            $id = addUser($db, $NomUtilisateur, $MotDePasse);
            
            if($id)
            {
                $response['status'] = 'success';
                $response['message'] = 'User added successfully';
                $response['idMessage'] = $id;
            }
            else
            {
                $response['status'] = 'failed';
                $response['message'] = 'User already exists';
            }
        }
        else {
            $response['status'] = 'error';
            $response['message'] = 'Missing parameters';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid request method';
    }

    echo json_encode($response);
} catch(Exception $e){
    echo json_encode(array("status" => "error", "message" => "Database connection failed: " . $e->getMessage()));
    exit();
}

?>
