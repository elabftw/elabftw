<?php
/**
 * \Elabftw\Elabftw\CrudInterface
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Interface for things like Steps, Links, Comments
 *
 */
interface CrudInterface
{
    /**
     * Read all the things
     *
     * @return array
     */
    public function readAll();

    /**
     * Destroy with id
     *
     * @param int $id
     * @return bool
     */
    public function destroy($id);

    /**
     * Detroy all the things
     *
     * @return bool
     */
    public function destroyAll();
}
