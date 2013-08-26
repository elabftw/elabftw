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
require_once('inc/common.php');
// SQL to get firstname and email
$sql = "SELECT firstname, lastname, email FROM users WHERE userid=".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

$body_societyofscience = "Dear Dr. ".$data['lastname'].",

The Strategy Committee and the Directorate of Society in Science - The Branco Weiss Fellowship have now had the opportunity to review the fellowship applications and to draw up the list of those applicants whom it would like to interview. Given the strong field of candidates, it is with great regret that I must inform you that the Committee has decided that it will not take your application forward. Please note that, given the large number of applications, we will not be able to provide individual feedback.

Hoping that you can find alternative means to develop your research plans, I kindly thank you for applying to Society in Science.

Sincerely yours,
Prof. Dr. Peter Chen
Society in Science - The Branco Weiss Fellowship";

$body_pnas = "Dear Prof. ".$data['lastname'].",

    I regret to inform you that the Editorial Board has rejected your manuscript. 
We receive many more good papers than we can publish, so the Board has to decide which ones go on for further
review. The Board concluded that your paper, while interesting, fell a
bit below the standards of novelty and importance necessary for further
consideration by the journal.

Once a paper has been rejected, it may not be resubmitted through an
Academy Member. Note that the copyright assignment conveyed at initial
submission is terminated.

Thank you for submitting to PNAS. I am sorry we cannot be more
encouraging this time, and I hope that you will consider us in the
future.

    Sincerely yours,
    Randy Schekman
    Editor-in-Chief";

$body_cell = "Dear ".$data['firstname'].",

Your manuscript has now been evaluated by three reviewers, and I am enclo
sing their comments.  I am sorry for the length of the review process.  U
nfortunately, the consensus recommendation is against publication of the 
paper in its current form in Cell.  As you will see, the reviewers find a
spects of the work to be well done and potentially interesting; however, 
each one raises a number of technical concerns about the results.  Perhap
s more importantly to my mind, they also question whether the overall con
clusions rise to the level of conceptual advance that would be required f
or publication.  It would therefore be premature to proceed further with 
the manuscript on the basis of the present results.  At this point, it is
 not clear that a revised manuscript would be a strong candidate for publ
ication, and it is therefore difficult for me to recommend resubmission.

I am sorry that the outcome for this manuscript was not more positive.  I
 do want to emphasize, however, that this is not intended to imply a lack
 of interest on our part in either your work in particular or this field 
in general, and we hope that you will continue to consider Cell for futur
e submissions.


Best wishes,

Lara

Lara Szewczak, Ph.D.
Scientific Editor, Cell";

$body_bioinformatics = "
On behalf of the Associate Editor of Bioinformatics, Martin Bishop, I would
 like to thank you for your time and effort in reviewing the above manuscri
pt.

After careful consideration of yours' and colleagues' criticisms, the final
 decision on this paper was: Reject after Review.

For your information, a copy of the decision is below with the reviewer and
 editor comments included (if applicable).

Many thanks for your assistance with the assessment of this submission.=

Best regards,

Alison Hutchins
Editorial Office
Bioinformatics

_______________________________________________
Decision letter:

Dear Mr. ".$data['lastname'].",

The reviews of your manuscript are now in hand. The reviewers had substanti
al concerns about the manuscript and the Associate Editor, Martin Bishop, h
as decided to reject your manuscript based on their advice.

As the journal receives more publishable manuscripts than its space will ac
commodate, acceptance must be limited to manuscripts receiving the most fav
ourable recommendations from reviewers.  Unfortunately, I must decline your
 request to have the manuscript published in Bioinformatics.   The Associat
e Editor's general comments and those of the reviewers, can be found at the
 foot of this e-mail. We will not consider any further versions of this man
uscript, but I hope that you find the comments useful should you revise you
r manuscript for another journal.

On behalf of the Executive Editor, I would like to thank you for considerin
g Bioinformatics to present your work and I look forward to the possibility
 of receiving other manuscripts from you in the future.

Best regards,
Alison Hutchins
Bioinformatics  Editorial office";

// Select journal
$journal = $_GET['journal'];
if ($journal === 'pnas') {
    $body = $body_pnas;
} elseif ($journal === 'cell') {
    $body = $body_cell;
} elseif ($journal === 'bioinformatics') {
    $body = $body_bioinformatics;
} elseif ($journal === 'society') {
    $body = $body_societyofscience;
} else {
    die('Bad journal type.');
}

// EMAIL
require_once('lib/swift_required.php');
// Create the message
$message = Swift_Message::newInstance()
// Give the message a subject
->setSubject('Your submission to '.$journal.' has been reviewed')
// Set the From address with an associative array
->setFrom(array('elabftw.net@gmail.com' => 'Nature Publishing Group'))
// Set the To addresses with an associative array
->setTo(array($data['email'] => $data['firstname'].' '.$data['lastname']))
// Give it a body
->setBody($body."

* This is NOT a real email; it is a joke from elabFTW *");
// SEND
$transport = Swift_SmtpTransport::newInstance($ini_arr['smtp_address'], $ini_arr['smtp_port'], $ini_arr['smtp_encryption'])
    ->setUsername($ini_arr['smtp_username'])
    ->setPassword($ini_arr['smtp_password']);
    $mailer = Swift_Mailer::newInstance($transport);
    $result = $mailer->send($message);
    if ($result){
$msg_arr[] = "Experiment successfully sent to ".$journal." for publication. You should get an answer promptly by email.";
$_SESSION['infos'] = $msg_arr;
session_write_close();
header('Location: experiments.php');
    } else {
$msg_arr[] = "There was an error sending the email to ".$email;
$_SESSION['errors'] = $msg_arr;
session_write_close();
header('Location: experiments.php');
    }

