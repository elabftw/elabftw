<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
// GENERAL
define("YES", "Oui");
define("NO", "Non");
define("CONFIG_UPDATE_OK", "Configuration mise à jour avec succès.");
define("ERROR_BUG", "Une erreur inattendue s'est produite ! Merci d'<a href='https://github.com/NicolasCARPi/elabftw/issues/'>ouvrir un rapport de bug sur GitHub</a> si vous pensez que c'est un bug.");
define("INVALID_ID", "Le paramètre id n'est pas valide !");
define("INVALID_USERID", "L'userid n'est pas valide.");
define("INVALID_TYPE", "Le paramètre type n'est pas valide.");
define("INVALID_FORMKEY", "La clé de formulaire n'est pas valide. Merci de réessayer.");
define("INVALID_EMAIL", "L'email n'est pas valide.");
define("INVALID_PASSWORD", "Mauvais mot de passe !");
define("INVALID_USER", "Pas d'utilisateur avec cet email ou l'utilisateur n'est pas dans votre équipe.");
define("PASSWORDS_DONT_MATCH", "Les mots de passe entrés diffèrent !");
define("PASSWORD_TOO_SHORT", "Le mot de passe doit contenir au moins 8 charactères.");
define("NEED_TITLE", "Il faut mettre un titre !");
define("NEED_PASSWORD", "Vous devez entrer un mot de passe !");
define("FIELD_MISSING", "Un champs obligatoire est manquant.");
define("NO_ACCESS_DIE", "Cette section est hors de votre portée.");

define("USERNAME", "Nom d'utilisateur");
define("PASSWORD", "Mot de passe");
define("FIRSTNAME", "Prénom");
define("LASTNAME", "Nom");
define("EMAIL", "Email");
define("TEAM", "Équipe");

define("DATE", "Date");
define("TITLE", "Titre");
define("INFOS", "Infos");
define("VISIBILITY", "Visibilité");
define("STATUS", "Status");

define("EDIT", "Editer");
define("SAVE", "Enregistrer");
define("SAVED", "Enregistré !");
define("CANCEL", "Annuler");
define("SAVING", "Enregistrement…");
define("SAVE_AND_BACK", "Enregistrer et revenir en arrière");
define("UPDATED", "Mis à jour !");

define("ACTION", "Action");
define("SHORTCUT", "Raccourci");
define("CREATE", "Créer");
define("SUBMIT", "Valider");
define("TODO", "TODO liste");

define("NAME", "Nom");
define("PHONE", "Téléphone");
define("MOBILE", "Mobile");
define("WEBSITE", "Site web");
define("SKYPE", "Skype");

// EMAILS
define("EMAIL_NEW_USER_SUBJECT", "[eLabFTW] Nouvel utilisateur enregistré");
define("EMAIL_NEW_USER_BODY_1", "Bonjour,
    Votre compte sur eLabFTW a été activé. Vous pouvez vous connecter :");
define("EMAIL_SEND_ERROR", "Il y a eu un problème lors de l'envoi de l'email ! L'erreur a été logguée.");
define("EMAIL_SUCCESS", "Email envoyé. Consultez votre boîte de réception.");
define('EMAIL_FOOTER', '

~~~
Email sent by eLabFTW
http://www.elabftw.net
Free open-source Lab Manager');

// ADMIN.PHP
define("ADMIN_TITLE", "Panneau d'administration");

define("ADMIN_VALIDATION_QUEUE", "Il y a des utilisateurs en attente de validation :");
define("ADMIN_VALIDATION_QUEUE_SUBMIT", "Valider");

define("ADMIN_MENU_TEAM", "Équipe");
define("ADMIN_MENU_USERS", "Utilisateurs");
define("ADMIN_MENU_STATUS", "Status");
define("ADMIN_MENU_ITEMSTYPES", "Catégories d'items");
define("ADMIN_MENU_EXPTPL", "Modèle d'expériences");
define("ADMIN_MENU_CSV", "Importer un CSV");

define("ADMIN_TEAM_H3", "Configurez votre équipe");
define("ADMIN_TEAM_DELETABLE_XP", "Les utilisateurs peuvent supprimer leurs expériences :");
define("ADMIN_TEAM_LINK_NAME", "Nom du lien dans le menu du haut :");
define("ADMIN_TEAM_LINK_HREF", "Adresse vers laquelle ce lien pointe :");
define("ADMIN_TEAM_STAMPLOGIN", "Identifiant pour le service externe de timestamping :");
define("ADMIN_TEAM_STAMPLOGIN_HELP", "Adresse email associée à votre compte sur Universign.com.");
define("ADMIN_TEAM_STAMPPASS", "Mot de passe pour le service externe de timestamping :");
define("ADMIN_TEAM_STAMPPASS_HELP", "Votre mot de passe Universign");

define("ADMIN_USERS_H3", "Éditer les utilisateurs");
define("ADMIN_USERS_VALIDATED", "Compte activé ?");
define("ADMIN_USERS_GROUP", "Groupe :");
define("ADMIN_USERS_RESET_PASSWORD", "Réinitialiser le mot de passe utilisateur :");
define("ADMIN_USERS_REPEAT_PASSWORD", "Répéter le nouveau mot de passe :");
define("ADMIN_USERS_BUTTON", "Éditer cet utilisateur");

define("ADMIN_DELETE_USER_H3", "ZONE DE DANGER");
define("ADMIN_DELETE_USER_H4", "Supprimer un compte");
define("ADMIN_DELETE_USER_HELP", "Taper l'ADRESSE EMAIL d'un membre pour le supprimer ainsi que toutes ses expériences pour toujours :");
define("ADMIN_DELETE_USER_CONFPASS", "Tapez votre mot de passe :");
define("ADMIN_DELETE_USER_BUTTON", "Supprimer cet utilisateur !");

define("ADMIN_STATUS_ADD_H3", "Ajouter un nouveau status");
define("ADMIN_STATUS_ADD_NEW", "Nom du nouveau status :");
define("ADMIN_STATUS_ADD_BUTTON", "Ajouter un nouveau status");
define("ADMIN_STATUS_EDIT_H3", "Éditer un status existant");
define("ADMIN_STATUS_EDIT_ALERT", "Supprimez toutes les expériences avec ce status avant de supprimer ce status.");
define("ADMIN_STATUS_EDIT_DEFAULT", "Status par défaut");

define("ADMIN_ITEMS_TYPES_H3", "Catégories d'items de la base de données");
define("ADMIN_ITEMS_TYPES_ALERT", "Supprimez tous les items de la base de données de cette catégorie avant de supprimer cette catégorie.");
define("ADMIN_ITEMS_TYPES_EDIT_NAME", "Éditer le nom :");
define("ADMIN_ITEMS_TYPES_ADD", "Ajouter une nouvelle catégorie d'items :");
define("ADMIN_ITEMS_TYPES_ADD_BUTTON", "Ajouter une nouvelle catégorie d'items");

define("ADMIN_EXPERIMENT_TEMPLATE_H3", "Modèle d'expérience commun");
define("ADMIN_EXPERIMENT_TEMPLATE_HELP", "Ceci est le texte par défaut affiché lorsqu'une expérience est créée.");

define("ADMIN_IMPORT_CSV_H3", "Importer un fichier CSV");
define("ADMIN_IMPORT_CSV_HELP", "Cette page vous permet d'importer un fichier .csv (un tableur Excel) dans la base de données.<br>D'abord il faut ouvrir le fichier (en .xls/.xlsx) dans Excel ou Libreoffice et l'enregistrer en .csv.<br>Pour que l'importation se passe bien, la première colonne doit être le titre. Le reste sera importé dans le corps. Vous pouvez faire un petit import de 3 lignes pour vérifier que tout fonctionne comme attendu avant de vous lancer dans l'import de milliers de références.");
define("ADMIN_IMPORT_CSV_HELP_STRONG", "Faîtes une sauvegarde de votre base de données (SQL) avant d'importer des milliers d'items !");
define("ADMIN_IMPORT_CSV_STEP_1", "1. Choisissez une catégorie d'item dans laquelle importer :");
define("ADMIN_IMPORT_CSV_STEP_2", "2. Choisissez un fichier CSV :");
define("ADMIN_IMPORT_CSV_BUTTON", "Importer le CSV");
define("ADMIN_IMPORT_CSV_MSG", "items ont été importés avec succès.");

// ADMIN-EXEC.PHP
define("ADMIN_USER_VALIDATED", "Ont été validés les utilisateurs avec l'ID :");
define("ADMIN_TEAM_ADDED", "Équipe ajoutée avec succès");
define("SYSADMIN_GRANT_SYSADMIN", "Seul un sysadmin peut nommer un autre sysadmin.");
define("USER_DELETED", "Tout a été supprimé avec succès.");

// CHANGE-PASS.PHP
define("CHANGE_PASS_TITLE", "Réinitialiser le mot de passe");
define("CHANGE_PASS_PASSWORD", "Nouveau mot de passe");
define("CHANGE_PASS_REPEAT_PASSWORD", "Tapez-le encore");
define("CHANGE_PASS_HELP", "8 charactères au minimum");
define("CHANGE_PASS_COMPLEXITY", "Complexité");
define("CHANGE_PASS_BUTTON", "Enregistrer le nouveau mot de passe");
// this is in JS code, so probably not a good idea to put ' or "
define("CHANGE_PASS_WEAK", "Mot de passe faible");
define("CHANGE_PASS_AVERAGE", "Mot de passe passable");
define("CHANGE_PASS_GOOD", "Bon mot de passe");
define("CHANGE_PASS_STRONG", "Mot de passe fort");
define("CHANGE_PASS_NO_WAY", "Pas moyen que ce soit votre vrai mot de passe !");

// CHECK_FOR_UPDATES.PHP
define("CHK_UPDATE_GIT", "Installez git pour vérifier les mises à jour.");
define("CHK_UPDATE_CURL", "Il vous faut installer l'extension curl pour php.");
define("CHK_UPDATE_UNKNOWN", "Branche inconnue !");
define("CHK_UPDATE_GITHUB", "Échec de la connexion à github.com pour vérifier les mises à jour.");
define("CHK_UPDATE_NEW", "Des mises à jour sont disponibles !");
define("CHK_UPDATE_MASTER", "Félicitations ! Vous utilisez la dernière version stable d'eLabFTW :)");
define("CHK_UPDATE_NEXT", "Félicitations ! Vous utilisez la dernière version de développement d'eLabFTW :)");

// CREATE_ITEM.PHP
define("CREATE_ITEM_WRONG_TYPE", "Mauvaise catégorie d'item !");
define("CREATE_ITEM_UNTITLED", "Sans titre");
define("CREATE_ITEM_SUCCESS", "Nouvel item créé avec succès.");

// DATABASE.PHP
define("DATABASE_TITLE", "Base de données");

// DELETE_FILE.PHP
define("DELETE_FILE_FILE", "Fichier");
define("DELETE_FILE_DELETED", "supprimé avec succès");

// DELETE.PHP
define("DELETE_NO_RIGHTS", "Vous n'avez pas les droits suffisants pour supprimer cette expérience.");
define("DELETE_EXP_SUCCESS", "L'expérience a été supprimée avec succès.");
define("DELETE_TPL_SUCCESS", "Le modéle a été supprimé avec succès.");
define("DELETE_ITEM_SUCCESS", "L'item a été supprimé avec succès.");
define("DELETE_ITEM_TYPE_SUCCESS", "La catégorie d'item a été supprimée avec succès.");
define("DELETE_STATUS_SUCCESS", "Le status a été supprimé avec succès.");

// DUPLICATE_ITEM.PHP
define("DUPLICATE_EXP_SUCCESS", "Expérince dupliquée avec succès.");
define("DUPLICATE_ITEM_SUCCESS", "Item dupliqué avec succès.");

// EXPERIMENTS.PHP
define("EXPERIMENTS_TITLE", "Expériences");

// LOCK.PHP
define("LOCK_NO_RIGHTS", "Vous n'avez pas les droits suffisants pour vérouiller/déverouiller.");
define("LOCK_LOCKED_BY", "Cette expérience a été vérouillée par");
define("LOCK_NO_EDIT", "Vous ne pouvez pas déverouiller ou éditer une expérience horodatée.");

// LOGIN-EXEC.PHP
define("LOGIN_FAILED", "Identification échouée. Soit vous avez mal tapé votre mot de passe, soit votre compte n'es pas encore activé.");

// LOGIN.PHP
define("LOGIN", "Connexion");
define("LOGIN_TOO_MUCH_FAILED", "Vous ne pouvez pas vous connecter en raison d'un trop grand nombre d'échec de connection.");
define("LOGIN_ATTEMPT_NB", "Nombre d'essais avant d'être banni pour");
define("LOGIN_MINUTES", "minutes :");
// in JS code
define("LOGIN_ENABLE_COOKIES", "Merci d'autoriser les cookies dans votre navigateur pour continuer.");
define("LOGIN_COOKIES_NOTE", "Note : il vous faut accepter les cookies pour vous connecter.");
define("LOGIN_H2", "Connectez-vous à votre compte");
define("LOGIN_FOOTER", "Pas de compte ? <a href='register.php'>Créez un compte</a> maintenant !<br>
Mot de passe oublié ? <a href='#' class='trigger'>Réinitialisez-le</a> !");
define("LOGIN_FOOTER_PLACEHOLDER", "Entrez votre adresse email");
define("LOGIN_FOOTER_BUTTON", "Envoyer le lien de réinitialisation");

// MAKE_CSV.PHP
define("CSV_TITLE", "Exporter en tableur");
define("CSV_READY", "Le fichier CSV est prêt :");

// MAKE_ZIP.PHP
define("ZIP_TITLE", "Créer une archive ZIP");
define("ZIP_READY", "Votre archive ZIP est prête :");

// PROFILE.PHP
define("PROFILE_TITLE", "Profil");
define("PROFILE_EXP_DONE", "expériences réalisées depuis");

// REGISTER-EXEC.PHP
define("REGISTER_USERNAME_USED", "Nom d'utilisateur déjà pris !");
define("REGISTER_EMAIL_USED", "Cette adresse email est déjà enregistrée !");
define("REGISTER_EMAIL_BODY", "Hi,
Someone registered a new account on eLabFTW. Head to the admin panel to activate the account !");
define("REGISTER_EMAIL_FAILED", "Could not send email to inform admin. Error was logged. Contact an admin directly to validate your account.");
define("REGISTER_SUCCESS_NEED_VALIDATION", "Registration successful :)<br>Your account must now be validated by an admin.<br>You will receive an email when it is done.");
define("REGISTER_SUCCESS", "Registration successful :)<br>Welcome to eLabFTW \o/");

// REGISTER.PHP
define("REGISTER_TITLE", "Créer un compte");
define("REGISTER_LOGOUT", "<a style='text-decoration:underline' href='logout.php'>Déconnectez-vous</a> avant d'enregistrer un autre compte.");
define("REGISTER_BACK_TO_LOGIN", "retour à la page de connexion");
define("REGISTER_H2", "Créez votre compte");
define("REGISTER_DROPLIST", "Choisissez une équipe");
define("REGISTER_CONFIRM_PASSWORD", "Confirmer le mot de passe");
define("REGISTER_PASSWORD_COMPLEXITY", "Complexité du mot de passe");
define("REGISTER_BUTTON", "créer");

// RESET-EXEC.PHP
define("RESET_SUCCESS", "New password updated. You can now login.");

// RESET-PASS.PHP
define("RESET_MAIL_SUBJECT", "[eLabFTW] Réinitialisation du mot de passe");
define("RESET_MAIL_BODY", "Bonjour,
Quelqu'un (probablement vous) avec l'adresse IP :"); 
define("RESET_MAIL_BODY2", "et le user agent :");
define("RESET_MAIL_BODY3", "a demandé un nouveau mot de passe pour eLabFTW.

Suivez ce lien pour changer votre mot de passe :");
define("RESET_NOT_FOUND", "Cette adresse email n'est pas dans la base de données !");

// REVISIONS.PHP
define("REVISIONS_TITLE", "Révisions");
define("REVISIONS_GO_BACK", "Retourner à l'expérience");
define("REVISIONS_LOCKED", "Vous ne pouvez pas restaurer une révision d'une expérience vérouillée !");
define("REVISIONS_CURRENT", "Actuel :");
define("REVISIONS_SAVED", "Enregistré le :");
define("REVISIONS_RESTORE", "Restaurer");

// SEARCH.PHP
define('SEARCH', 'Recherche');
define("SEARCH_TITLE", "Recherche avancée");
define("SEARCH_BACK", "Retourner à la liste des expériences");
define("SEARCH_ONLY", "Rechercher uniquement dans les expériences de :");
define("SEARCH_YOU", "Vous-même");
define("SEARCH_EVERYONE", "chercher dans les expérinces de toute l'équipe");
define("SEARCH_IN", "Chercher dans");
define("SEARCH_DATE", "Où la date est entre");
define("SEARCH_AND", "et");
define("SEARCH_AND_TITLE", "Et le titre contient");
define("SEARCH_AND_BODY", "Et le corps contient");
define("SEARCH_AND_STATUS", "Et le status est");
define("SEARCH_SELECT_STATUS", "choisir un status");
define("SEARCH_AND_RATING", "Et l'évaluation est");
define("SEARCH_STARS", "choisir le nombre d'étoiles");
define("SEARCH_UNRATED", "Pas d'étoiles");
define("SEARCH_BUTTON", "Lancer la recherche");
define("SEARCH_EXPORT", "Exporter ce résultat :");
define("SEARCH_SORRY", "Désolé, aucun résultat pour les critères sélectionnés.");

// SYSCONFIG.PHP
define("SYSCONFIG_TITLE", "eLabFTW configuration");
define("SYSCONFIG_TEAMS", "Équipes");
define("SYSCONFIG_SERVER", "Serveur");
define("SYSCONFIG_TIMESTAMP", "Horodatage");
define("SYSCONFIG_SECURITY", "Securité");
define("SYSCONFIG_1_H3_1", "Ajouter une nouvelle équipe");
define("SYSCONFIG_1_H3_2", "Éditer les équipes existantes");
define("SYSCONFIG_MEMBERS", "Membres");
define("SYSCONFIG_ITEMS", "Items");
define("SYSCONFIG_CREATED", "Créée");
define("SYSCONFIG_2_H3", "Paramètres avancés");
define("SYSCONFIG_DEBUG", "Activer le mode debug :");
define("SYSCONFIG_DEBUG_HELP", "Lorsque ce mode est activé, le contenu des variables \$_SESSION et \$_COOKIES seront affichés dans le pied de page pour les admins.");
define("SYSCONFIG_PROXY", "Adresse du proxy :");
define("SYSCONFIG_PROXY_HELP", "Si vous êtes derrière un pare-feu/proxy, entrez l'adresse ici. Exemple : http://proxy.example.com:3128");
define("SYSCONFIG_PATH", "Chemin complet du dossier où est eLabFTW");
define("SYSCONFIG_PATH_HELP", "Ceci est en réalité le hash md5 du chemin. Vous n'avez probablement pas besoin de changer ça, sauf si vous avez bougé l'installation de place.");
define("SYSCONFIG_3_H3", "Configuration de l'horodatage Universign");
define("SYSCONFIG_STAMPSHARE", "Les équipes peuvent utiliser les informations de connexion suivantes :"); 
define("SYSCONFIG_STAMPSHARE_HELP", "Vous pouvez contrôler si les équipes peuvent utiliser le compte Universign global ou pas. Si <em>non</em> est sélectionné, l'administrateur d'équipe doit renseigner les identifiants pour son équipe.");
define("SYSCONFIG_STAMPLOGIN", "Identifiant pour le service externe d'horodatage :");
define("SYSCONFIG_STAMPLOGIN_HELP", "Doit être une adresse email.");
define("SYSCONFIG_STAMPPASS", "Mot de passe pour le service externe d'horodatage :");
define("SYSCONFIG_STAMPPASS_HELP", "Ce mot de passe sera stocké en clair dans la base de données ! Choisissez-en un d'unique.");
define("SYSCONFIG_4_H3", "Paramètres de sécurité");
define("SYSCONFIG_ADMIN_VALIDATE", "Les nouveaux inscrits nécessitent une validation par l'admin :");
define("SYSCONFIG_ADMIN_VALIDATE_HELP", "Mettez oui pour plus de sécurité.");
define("SYSCONFIG_LOGIN_TRIES", "Nombre d'essais de connexion autorisés :");
define("SYSCONFIG_LOGIN_TRIES_HELP", "3 n'est peut-être pas assez. À vous de voir :)");
define("SYSCONFIG_BAN_TIME", "Temps de bannissement (en minutes) :");
define("SYSCONFIG_BAN_TIME_HELP", "Pour identifier un utilisateur, un hash md5 de l'user agent + IP est utilisé. Car se baser seulement sur l'adresse IP pourrait causer des problèmes.");
define("SYSCONFIG_5_H3", "Configuration SMTP");
define("SYSCONFIG_5_HELP", "Sans possibilité d'envoyer des emails, les utilisateurs ne pourrant pas réinitialiser leurs mots de passe. Il est recommandé d'utiliser un compte spécifique Mandrill.com (ou gmail).");
define("SYSCONFIG_SMTP_ADDRESS", "Adresse du serveur SMTP :");
define("SYSCONFIG_SMTP_ADDRESS_HELP", "smtp.mandrillapp.com");
define("SYSCONFIG_SMTP_ENCRYPTION", "Encryption SMTP (TLS ou STARTSSL) :");
define("SYSCONFIG_SMTP_ENCRYPTION_HELP", "Probablement TLS.");
define("SYSCONFIG_SMTP_PORT", "Port SMTP :");
define("SYSCONFIG_SMTP_PORT_HELP", "Le port par défaut est 587.");
define("SYSCONFIG_SMTP_USERNAME", "Nom d'utilisateur SMTP :");
define("SYSCONFIG_SMTP_PASSWORD", "Mot de passe SMTP :");

// TEAM.PHP
define("TEAM_TITLE", "Équipe");
define("TEAM_STATISTICS", "Statistiques");
define("TEAM_TIPS_TRICKS", "Astuces");
define("TEAM_BELONG", "Vous appartenez à l'équipe");
define("TEAM_TEAM", "");
define("TEAM_TOTAL_OF", "Il y a un total de");
define("TEAM_EXP_BY", "expériences par");
define("TEAM_DIFF_USERS", "utilisateurs différents");
define("TEAM_ITEMS_DB", "items dans la base de données.");
define("TEAM_TIP_1", "Vous pouvez afficher une TODO liste en pressant 't'");
define("TEAM_TIP_2", "Vous pouvez avoir des modèles d'expériences (<a href='ucp.php?tab=3'>Panneau de contrôle utilisateur</a>)");
define("TEAM_TIP_3", "L'administrateur d'une équipe peut éditer les status et les types d'items disponibles (<a href='admin.php?tab=4'>Panneau d'administration</a>)");
define("TEAM_TIP_4", "Si vous faîtes Ctrl Maj D dans la fenêtre d'édition, la date du jour apparaîtra sous le curseur.");
define("TEAM_TIP_5", "Des raccourcis claviers personnalisables sont disponibles (<a href='ucp.php?tab=2'>Panneau de contrôle utilisateur</a>)");
define("TEAM_TIP_6", "Vous pouvez dupliquer des expériences en un seul clic");
define("TEAM_TIP_7", "Cliquez un tag pour lister tous les items avec ce tag");
define("TEAM_TIP_8", "Créez un compte avec <a href='https://www.universign.eu/en/timestamp'>Universign</a> pour commencer à horodater des expériences.");
define("TEAM_TIP_9", "Seule une expérience vérouillée peut être horodatée.");
define("TEAM_TIP_10", "Une fois vérouillée, une expérience ne peut pas être modifiée ou déverouillée. Seuls des commentaires peuvent être ajoutés.");

// TIMESTAMP.PHP
define("TIMESTAMP_CONFIG_ERROR", "L'horodatage n'est pas configuré. Merci de lire <a href='https://github.com/NicolasCARPi/elabftw/wiki/finalizing#setting-up-timestamping'>le wiki</a>.");
define("TIMESTAMP_ERROR", "Il y a eu une erreur dans l'horodatage. Les informations d'authentification sont peut-être fausses, ou il n'y a plus de crédits.");
define("TIMESTAMP_USER_ERROR", "Il y a eu une erreur dans l'horodatage. L'expérience n'est PAS horodatée. L'erreur a été logguée.");
define("TIMESTAMP_SUCCESS", "Expérience horodatée avec succès. Le PDF horodaté peut être téléchargé ci-dessous.");

// UCP-EXEC.PHP
define("UCP_TITLE", "Panneau de contrôle utilisateur");
define("UCP_PASSWORD_SUCCESS", "Mot de passe mis à jour !");
define("UCP_PROFILE_UPDATED", "Profil mis à jour !");
define("UCP_ENTER_PASSWORD", "Entrez votre mot de passe pour éditer les informations !");
define("UCP_PREFS_UPDATED", "Vos préférences on été mises à jour.");
define("UCP_TPL_NAME", "Il faut spécifier un nom pour le modèle !");
define("UCP_TPL_SHORT", "Le nom du modèle doit être long d'au moins 3 caractères.");
define("UCP_TPL_SUCCESS", "Modèle d'expérience ajouté avec succès.");
define("UCP_TPL_EDITED", "Modèle d'expérience édité avec succès.");
define('UCP_TPL_PLACEHOLDER', 'Nom du modèle');
define("UCP_ACCOUNT", "Compte");
define("UCP_PREFERENCES", "Préférences");
define("UCP_TPL", "Modèles");
define("UCP_H4_1", "Modifier vos informations personnelles.");
define("UCP_H4_2", "Modifier votre identité");
define("UCP_H4_3", "Modifier votre mot de passe ");
define("UCP_NEWPASS", "Nouveau mot de passe");
define("UCP_CNEWPASS", "Confirmer le nouveau mot de passe");
define("UCP_H4_4", "Modifier vos informations de contact");
define("UCP_BUTTON_1", "Mettre à jour le profil");
define("UCP_H3_1", "AFFICHAGE");
define("UCP_DEFAULT", "Defaut");
define("UCP_COMPACT", "Compact");
define("UCP_ORDER_BY", "Ordre basé sur :");
define("UCP_ITEM_ID", "Item ID");
define("UCP_WITH", "avec");
define("UCP_NEWER", "les plus récents d'abord");
define("UCP_OLDER", "les plus anciens d'abord");
define("UCP_LIMIT", "Items par page :");
define("UCP_H3_2", "RACCOURCIS CLAVIER");
define("UCP_H3_3", "ALERTE");
define("UCP_CLOSE_WARNING", "Afficher un avertissement avant de fermer un onglet d'édition ?");
define("UCP_ADD_TPL", "Ajouter un modèle");
define("UCP_EDIT_BUTTON", "Éditer un modèle");
define('LANGUAGE', 'Language');

// VIEW DB
define("NOTHING_TO_SHOW", "Rien à afficher avec cet ID.");
define("LAST_MODIFIED_BY", "Dernière modification par");
define("ON", "le");

// VIEW XP
define("VIEW_XP_FORBIDDEN", "<strong>Accès interdit :</strong> le paramètre de visibilité de cette expérience est 'propriétaire seulement'.");
define("VIEW_XP_RO", "<strong>Mode lecture seule :</strong> cette expérience appartient à");
define("VIEW_XP_TIMESTAMPED", "Expérience horodatée par");
define("AT", "à");
define("VIEW_XP_ELABID", "eLabID unique :");
define("COMMENTS", "Commentaires");
define("ADD_COMMENT", "Ajouter un commentaire");
define("DELETE_THIS", "Supprimer ça ?"); // in JS
define("CONFIRM_STAMP", "Une expérience horodatée ne peut plus être éditée ! Êtes-vous sûr de vouloir faire ça ?"); // in JS

// TAGCLOUD
define("TAGCLOUD_H4", "Nuage de tags");
define("NOT_ENOUGH_TAGS", "Pas assez de tags pour faire un nuage.");

// STATISTICS
define("STATISTICS_H4", "Statistiques");
define("STATISTICS_NOT_YET", "Pas de statistiques disponibles.");
define("STATISTICS_EXP_FOR", "Expériences pour");

// FILE UPLOAD
define("FILE_UPLOAD_H3", "Joindre un fichier.");

// SHOW DB
define("SHOW_DB_CREATE_NEW", "CRÉER NOUVEAU");
define("SHOW_DB_FILTER_TYPE", "FILTRER LES CATÉGORIES");
define("FOUND", "Trouvé");
define("RESULTS", "résultats");
define("FOUND_1", "1 résultat trouvé.");
define("FOUND_0", "Pas de résultat.");
define("SHOW_DB_WELCOME", "<strong>Bienvenue sur eLabFTW.</strong>Choisissez un item dans la liste «Créer nouveau» pour commencer à remplire votre base de données.");
define("SHOW_DB_LAST_10", "Liste des 10 derniers ajouts :");

// SHOW XP
define("SHOW_XP_MORE", "Afficher plus");
define("SHOW_XP_CREATE", "Créer une expérience");
define("SHOW_XP_CREATE_TPL", "Créer depuis un modèle");
define("SHOW_XP_FILTER_STATUS", "FILTRER LES STATUS");
define("SHOW_XP_NO_TPL", "<strong>Vous n'avez pas encore créé de modèles.</strong> Rendez-vous dans <a href='ucp.php?tab=3'>votre panneau de contrôle</a> pour en ajouter !");
define("SHOW_XP_NO_EXP", "<strong>Bienvenue sur eLabFTW.</strong>Cliquez le bouton <img src='img/add.png' alt='Create experiment' /><a href='create_item.php?type=exp'>Créer une expérience</a> pour démarrer.");

// EDIT DB
define("LOCKED_NO_EDIT", "<strong>Cet item est vérouillé.</strong> Vous ne pouvez pas l'éditer.");
define("TAGS", "Tags");
define("CLOSE_WARNING", "Êtes vous sûr de vouloir quitter cette page ? Tout changement non sauvegardé sera perdu !");

// EDIT XP
define("EDIT_XP_NO_RIGHTS", "<strong>Édition interdite :</strong> cette expérience ne vous appartient pas !");
define("EDIT_XP_TAGS_HELP", "cliquer un tag pour le supprimer");
define("EDIT_XP_ADD_TAG", "Ajouter un tag");
define("ONLY_THE_TEAM", "Seulement l'équipe");
define("EXPERIMENT", "Experience");
define("LINKED_ITEMS", "Items liés");
define("ADD_LINK", "Ajouter un lien");
define("ADD_LINK_PLACEHOLDER", "depuis la base de données");
define("SHOW_HISTORY", "Voir historique");

// INC/HEAD.PHP
define('LOGGED_IN_AS', 'Salutations,');
define('SETTINGS', 'Paramètres');
define('LOGOUT', 'Déconnexion');

// INC/FOOTER.PHP
define('CHECK_FOR_UPDATES', 'METTRE À JOUR');
define('SYSADMIN_PANEL', 'PANNEAU DU SYSADMIN');
define('ADMIN_PANEL', "PANNEAU D'ADMINISTRATION");
define('POWERED_BY', 'Propulsé par');
define('PAGE_GENERATED', 'Page générée en');
