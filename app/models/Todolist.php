<?php
/**
 * \Elabftw\Elabftw\Todolist
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * All about the todolist
 */
class Todolist extends AbstractTodolist
{
    /**
    * 
    */
    protected function getTableName(){
        return "todolist";
    }

}