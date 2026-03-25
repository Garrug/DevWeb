<?php

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


/**
 * @fn function envoyer_mail($destinataire, $sujet, $contenu)
 * @date 23 mars 2026
 * @brief envoyer_mail envoie un mail dont le destinataire, le sujet et le contenu
 * sont passés en paramètre. Il effectue cela en se connectant au serveur SMTP de mailtrap.io
 * @param $destinataire Adresse mail du destinataire
 * @param $sujet		Sujet du mail
 * @param $contenu		Contenu du mail (peut être du texte HTML)
 * 
 * 
 * */
function envoyer_mail($destinataire, $sujet, $contenu) {
	
	
	/*     Création du mail et initialisation de ses paramètres     */
	
	$mail = new PHPMailer(true);
	
	$mail->isSMTP();	
	$mail->Host = 'sandbox.smtp.mailtrap.io';
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = 'tls';
	$mail->Port = 2525;
	$mail->Username = '';  /* A COMPLETER */
	$mail->Password = '';	 /* A COMPLETER */
	
	
	/*     Initialisation des propriétés et du contenu du mail     */
	
	$mail->isHTML(true);
	$mail->setFrom('noreply@stageflow.fr');
	$mail->addAddress($destinataire);
	$mail->Subject = $sujet;
	$mail->Body = $contenu;
	
	

	/*     Envoi du mail et vérification de l'adresse     */

	try {
		if (!PHPMailer::validateAddress($destinataire)) {
			echo "Non valid address";
			throw new Exception("Adresse e-mail invalide : $recipient_email");
		}
		
		$mail->send();
		echo "OK";
    
	} catch (Exception $e) {
		echo "ERROR -> " . $mail->ErrorInfo;
	
  }
	
	
	
}

envoyer_mail('utilisateur@stageflow.fr', "Confirmation StageFlow", "Test PHPMailer.");

?>
