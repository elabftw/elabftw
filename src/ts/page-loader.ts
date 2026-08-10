/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * This page will conditionally load ts code depending on the page or presence of an element
 */
const mode = new URLSearchParams(window.location.search).get('mode');
import { core } from './core';

if (document.getElementById('ketcher-root')) {
  void import(
    /* webpackChunkName: 'chem-editor' */
    './chem-editor'
  );
}

if (document.getElementById('syc-root')) {
  void import(
    /* webpackChunkName: 'opencloning' */
    './opencloning'
  );
}

if (window.location.pathname === '/ucp.php') {
  void import(
    /* webpackChunkName: 'ucp' */
    './ucp'
  );
}

if (window.location.pathname === '/scheduler.php') {
  void import(
    /* webpackChunkName: 'scheduler' */
    './scheduler'
  );
}

if (document.getElementById('users-table')) {
  void import(
    /* webpackChunkName: 'editusers' */
    './editusers'
  );
}

if (window.location.pathname === '/sysconfig.php') {
  void import(
    /* webpackChunkName: 'sysconfig' */
    './sysconfig'
  );
}

if (document.getElementById('compounds-table')) {
  void import(
    /* webpackChunkName: 'compounds-table' */
    './compounds-table'
  );
}

if (window.location.pathname === '/profile.php') {
  void import(
    /* webpackChunkName: 'profile' */
    './profile'
  );
}

if (document.getElementById('stepsDiv')) {
  void import(
    /* webpackChunkName: 'steps-links' */
    './steps-links'
  );
}

if (mode === 'view') {
  void import(
    /* webpackChunkName: 'view' */
    './view'
  );
}

if (mode === 'edit') {
  void import(
    /* webpackChunkName: 'edit' */
    './edit'
  );
}

if (window.location.pathname === '/team.php') {
  void import(
    /* webpackChunkName: 'team' */
    './team'
  );
}

if (window.location.pathname === '/team.php') {
  void import(
    /* webpackChunkName: 'team' */
    './team'
  );
}

if (document.getElementById('uploadsDiv')) {
  void import(
    /* webpackChunkName: 'uploads' */
    './uploads'
  );
}

if (window.location.pathname === '/revisions.php') {
  void import(
    /* webpackChunkName: 'revisions' */
    './revisions'
  );
}

if (document.getElementById('topToolbar')) {
  void import(
    /* webpackChunkName: 'toolbar' */
    './toolbar'
  );
}

if (document.getElementById('todolistPanel') && !core.isAnon) {
  void import(
    /* webpackChunkName: 'todolist' */
    './todolist'
  );
}
