<?php
//namespace nicolascarpi\elabftw;

class MakePdf {
    /**
     * Make the pdf file
     * @param int $id The id of the item to pdfize
     * @param string $type The type of item can be 'experiments' or 'items'
     * @param string $out Do we put it in a file or out to the browser ? Default is browser
     * @return string|null either the pdf of the path to pdf file
     */
    public function create($id, $type, $out = 'browser')
    {
        global $pdo;

        // SQL to get title, body and date
        $sql = "SELECT * FROM ".$type." WHERE id = ".$id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $data = $req->fetch();
        $title = stripslashes($data['title']);
        $date = $data['date'];
        // the name of the pdf is needed in make_zip
        $clean_title = $date."-".preg_replace('/[^A-Za-z0-9]/', '_', $title);
        $body = stripslashes($data['body']);
        // ELABID
        if ($type === 'experiments') {
            $elabid = $data['elabid'];
        }
        // LOCK BLOCK
        if ($data['locked'] == '1' && $type == 'experiments') {
            // get info about the locker
            $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid LIMIT 1";
            $reqlock = $pdo->prepare($sql);
            $reqlock->execute(array(
                'userid' => $data['lockedby']
            ));
            $lockuser = $reqlock->fetch();

            // separate date and time
            if (isset($data['lockedwhen'])) {
                $lockdate = explode(' ', $data['lockedwhen']);
                // this will be added after the URL
                $lockinfo = "<p class='elabid'>locked by ".$lockuser['firstname']." ".$lockuser['lastname']." on ".$lockdate[0]." at ".$lockdate[1].".</p>";
            } else {
                $lockinfo = "";
            }
        }
        $req->closeCursor();

        // SQL to get firstname + lastname
        $sql = "SELECT firstname,lastname FROM users WHERE userid = :userid";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'userid' => $data['userid']
        ));
        $data = $req->fetch();
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $req->closeCursor();

        // SQL to get tags
        $sql = "SELECT tag FROM ".$type."_tags WHERE item_id = ".$id;
        $req = $pdo->prepare($sql);
        $req->execute();
        $tags = null;
        while ($data = $req->fetch()) {
            $tags .= $data['tag'].' ';
        }
        $req->closeCursor();

        // SQL to get comments
        // check if there is something to display first
        // get all comments, and infos on the commenter associated with this experiment
        $sql = "SELECT * FROM experiments_comments
            LEFT JOIN users ON (experiments_comments.userid = users.userid)
            WHERE exp_id = :id
            ORDER BY experiments_comments.datetime DESC";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'id' => $id
        ));
        // if we have comments
        if ($req->rowCount() > 0) {
            $comments_block = "";
            $comments_block .= "<section>";
            if ($req->rowCount() === 1) {
                $comments_block .= "<h3>Comment :</h3>";
            } else {
                $comments_block .= "<h3>Comments :</h3>";
            }
            // there is comments to display
            while ($comments = $req->fetch()) {
                if (empty($comments['firstname'])) {
                    $comments['firstname'] = '[deleted]';
                }
                $comments_block .= "<p>On ".$comments['datetime']." ".$comments['firstname']." ".$comments['lastname']." wrote :<br />";
                $comments_block .= "<p>".$comments['comment']."</p>";

            }
            $comments_block .= "</section>";
        } else { // no comments to display
            $comments_block = '';
        }

        // build content of page
        // add css
        $content = "<link rel='stylesheet' media='all' href='css/pdf.css' />";
        $content .= "<h1>".$title."</h1>
            Date : ".format_date($date)."<br />
            <em>Tags : ".$tags."</em><br />
            Made by : ".$firstname." ".$lastname."
            <hr><p>".$body."</p>";
        // Construct URL
        $url = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];

        // ATTACHED FILES
        // SQL to get attached files
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':type', $type);
        $req->execute();
        $real_name = array();
        $comment = array();
        $md5 = array();
        while ($uploads = $req->fetch()) {
            $real_name[] = $uploads['real_name'];
            $comment[] = $uploads['comment'];
            $md5[] = $uploads['md5'];
        }
        // do we have files attached ?
        if (count($real_name) > 0) {
            $content .= "<section>";
            if (count($real_name) === 1) {
                $content .= "<h3>Attached file :</h3>";
            } else {
                $content .= "<h3>Attached files :</h3>";
            }
            $content .= "<ul>";
            $real_name_cnt = count($real_name);
            for ($i = 0; $i < $real_name_cnt; $i++) {
                $content .= "<li>".$real_name[$i];
                // add a comment ? don't add if it's the default text
                if ($comment[$i] != 'Click to add a comment') {
                    $content .= " (".stripslashes(htmlspecialchars_decode($comment[$i])).")";
                }
                // add md5 sum ? don't add if we don't have it
                if (strlen($md5[$i]) == '32') { // we have md5 sum
                    $content .= "<br>md5 : ".$md5[$i];
                }
                $content .= "</li>";
            }
            $content .= "</ul></section>";
        }
        // EXPERIMENTS
        if ($type === 'experiments') {
            if ($out === 'browser') {
                $url = str_replace('make_pdf.php', 'experiments.php', $url);
            } else { // call from make_zip or timestamp.php
                $url = str_replace(array('make_zip.php', 'app/timestamp.php'), 'experiments.php', $url);
            }
            $full_url = $url."?mode=view&id=".$id;


            // SQL to get linked items
            $sql = "SELECT experiments_links.*,
                experiments_links.link_id AS item_id,
                items.title AS title,
                items_types.name AS type
                FROM experiments_links
                LEFT JOIN items ON (experiments_links.link_id = items.id)
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE item_id = ".$id;
            $req = $pdo->prepare($sql);
            $req->execute();
            $links_id_arr = array();
            $links_title_arr = array();
            $links_type_arr = array();
            // we put what we need in arrays
            while ($links = $req->fetch()) {
                $links_id_arr[] = $links['item_id'];
                $links_title_arr[] = $links['title'];
                $links_type_arr[] = $links['type'];
            }
            // only display this section if there is something to display
            if ($req->rowCount() > 0) {
                $content .= '<section>';
                if ($req->rowCount() === 1) {
                    $content .= "<h3>Linked item :</h3>";
                } else {
                    $content .= "<h3>Linked items :</h3>";
                }
                $content .= "<ul>";
                $row_cnt = $req->rowCount();
                for ($i=0; $i<$row_cnt; $i++) {
                    // we need the url of the displayed item
                    if ($out === 'browser') {
                        $item_url = str_replace('experiments.php', 'database.php', $url);
                    } else { // call from make_zip or timestamp.php
                        $item_url = str_replace(array('experiments.php', 'app/timestamp.php'), 'database.php', $url);
                    }
                    $full_item_url = $item_url."?mode=view&id=".$links_id_arr[$i];

                    $content .= "<li>[".$links_type_arr[$i]."] - <a href='".$full_item_url."'>".$links_title_arr[$i]."</a></li>";
                }
                $content .= "</ul></section>";
            }

            // Add comments
            $content .= $comments_block;
            // ELABID and URL
            $content .= "<p class='elabid'>elabid : ".$elabid."</p>";
            $content .= "<p class='elabid'>link : <a href='".$full_url."'>".$full_url."</a></p>";

        } else { // ITEM
            if ($out === 'browser') {
                $url = str_replace('make_pdf.php', 'database.php', $url);
            } else { // call from make_zip
                $url = str_replace('make_zip.php', 'database.php', $url);
            }
            $full_url = $url."?mode=view&id=".$id;
            $content .= "<p>URL : <a href='".$full_url."'>".$full_url."</a></p>";
        }


        if (isset($lockinfo)) {
            $content .= $lockinfo;
        }

        // FOOTER
        $content .= "<footer>PDF generated with <a href='http://www.elabftw.net'>elabftw</a>, a free and open source lab notebook</footer>";


        // Generate pdf with mpdf
        require_once ELAB_ROOT.'vendor/autoload.php';
        $mpdf = new mPDF();

        $mpdf->SetAuthor($firstname.' '.$lastname);
        $mpdf->SetTitle($title);
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetKeywords($tags);
        $mpdf->WriteHTML($content);

        if ($type == 'experiments') {
            // used by make_zip
            if ($out != 'browser') {
                $mpdf->Output($out.'/'.$clean_title.'.pdf', 'F');
                return $clean_title.'.pdf';
            } else {
                $mpdf->Output($clean_title.'.pdf', 'I');
            }
        } else { // database item(s)
            // used by make_zip
            if ($out != 'browser') {
                $mpdf->Output($out.'/'.$clean_title.'.pdf', 'F');
                return $clean_title.'.pdf';
            } else {
                $mpdf->Output($clean_title.'.pdf', 'I');
            }
        }
    }
}
