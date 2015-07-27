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
namespace Elabftw\Elabftw;

use \Elabftw\Elabftw\Db;

class SysConfig
{
    private $pdo;

    public function __construct()
    {
        $db = new Db();
        $this->pdo = $db->connect();
    }

    public function addTeam($name)
    {
        // add to the teams table
        $sql = 'INSERT INTO teams (team_name, link_name, link_href) VALUES (:team_name, :link_name, :link_href)';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team_name', $name);
        $req->bindValue(':link_name', 'Documentation');
        $req->bindValue(':link_href', 'doc/_build/html/');
        $result1 = $req->execute();
        // grab the team ID
        $new_team_id = $this->pdo->lastInsertId();

        // now we need to insert a new default set of status for the newly created team
        $sql = "INSERT INTO status (team, name, color, is_default) VALUES
        (:team, 'Running', '0096ff', 1),
        (:team, 'Success', '00ac00', 0),
        (:team, 'Need to be redone', 'c0c0c0', 0),
        (:team, 'Fail', 'ff0000', 0);";
        $req = $this->pdo->prepare($sql);
        $req->bindValue(':team', $new_team_id);
        $result2 = $req->execute();

        // now we need to insert a new default set of items_types for the newly created team
        $sql = "INSERT INTO `items_types` (`team`, `name`, `bgcolor`, `template`) VALUES
    (:team, 'Antibody', '31a700', '<p><strong>Host :</strong></p>\r\n<p><strong>Target :</strong></p>\r\n<p><strong>Dilution to use :</strong></p>\r\n<p>Don''t forget to add the datasheet !</p>'),
    (:team, 'Plasmid', '29AEB9', '<p><strong>Concentration : </strong></p>\r\n<p><strong>Resistances : </strong></p>\r\n<p><strong>Backbone :</strong></p>\r\n<p><strong><br /></strong></p>'),
    (:team, 'siRNA', '0064ff', '<p><strong>Sequence :</strong></p>\r\n<p><strong>Target :</strong></p>\r\n<p><strong>Concentration :</strong></p>\r\n<p><strong>Buffer :</strong></p>'),
    (:team, 'Drugs', 'fd00fe', '<p><strong>Action :</strong> &nbsp;<strong> </strong></p>\r\n<p><strong>Concentration :</strong>&nbsp;</p>\r\n<p><strong>Use at :</strong>&nbsp;</p>\r\n<p><strong>Buffer :</strong> </p>'),
    (:team, 'Crystal', '84ff00', '<p>Edit me</p>');";
        $req = $this->pdo->prepare($sql);
        $req->bindValue(':team', $new_team_id);
        $result3 = $req->execute();

        // now we need to insert a new default experiment template for the newly created team
        $sql = "INSERT INTO `experiments_templates` (`team`, `body`, `name`, `userid`) VALUES
        (:team, '<p><span style=\"font-size: 14pt;\"><strong>Goal :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style=\"font-size: 14pt;\"><strong>Procedure :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style=\"font-size: 14pt;\"><strong>Results :</strong></span></p><p>&nbsp;</p>', 'default', 0);";
        $req = $this->pdo->prepare($sql);
        $req->bindValue(':team', $new_team_id);
        $result4 = $req->execute();

        if ($result1 && $result2 && $result3 && $result4) {
            return true;
        }
        return false;
    }
}
