/*
 * In this file we define which icons we import (tree shaking feature) from font-awesome
 * This way we don't import all the icons \o/
 *
 */
'use strict';

const fontawesome = require('@fortawesome/fontawesome');


// SOLID
import { faCheckSquare, faCogs, faComments, faDna, faDownload, faFileArchive, faHistory, faInfoCircle, faLink, faLock, faLockOpen, faPaintBrush, faPaperclip, faPencilAlt, faQuestionCircle, faSignOutAlt, faTags, faTimes } from '@fortawesome/fontawesome-free-solid';
fontawesome.library.add(faCheckSquare);
fontawesome.library.add(faCogs);
fontawesome.library.add(faComments);
fontawesome.library.add(faDna);
fontawesome.library.add(faDownload);
fontawesome.library.add(faFileArchive);
fontawesome.library.add(faHistory);
fontawesome.library.add(faInfoCircle);
fontawesome.library.add(faLink);
fontawesome.library.add(faLock);
fontawesome.library.add(faLockOpen);
fontawesome.library.add(faPaintBrush);
fontawesome.library.add(faPaperclip);
fontawesome.library.add(faPencilAlt);
fontawesome.library.add(faQuestionCircle);
fontawesome.library.add(faSignOutAlt);
fontawesome.library.add(faTags);
fontawesome.library.add(faTimes);

// REGULAR
import { faCalendarAlt, faCopy, faFilePdf } from '@fortawesome/fontawesome-free-regular';
fontawesome.library.add(faCalendarAlt);
fontawesome.library.add(faCopy);
fontawesome.library.add(faFilePdf);

// BRANDS
import { faGithub, faTwitter } from '@fortawesome/fontawesome-free-brands';
fontawesome.library.add(faTwitter);
fontawesome.library.add(faGithub);
