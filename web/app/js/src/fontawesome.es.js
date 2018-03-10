/*
 * In this file we define which icons we import (tree shaking feature) from font-awesome
 * This way we don't import all the icons \o/
 *
 */
'use strict';

const fontawesome = require('@fortawesome/fontawesome');


// SOLID
import {
    faChartPie,
    faCheck,
    faCheckSquare,
    faChevronCircleLeft,
    faClipboardCheck,
    faCloud,
    faCogs,
    faComments,
    faDna,
    faDownload,
    faEye,
    faFileArchive,
    faHistory,
    faInfoCircle,
    faLink,
    faLock,
    faLockOpen,
    faMinusCircle,
    faPaintBrush,
    faPaperclip,
    faPencilAlt,
    faPlusCircle,
    faQuestionCircle,
    faSignOutAlt,
    faSyncAlt,
    faTags,
    faTimes,
    faUpload,
    faUser,
} from '@fortawesome/fontawesome-free-solid';

fontawesome.library.add(faChartPie);
fontawesome.library.add(faCheck);
fontawesome.library.add(faCheckSquare);
fontawesome.library.add(faChevronCircleLeft);
fontawesome.library.add(faClipboardCheck);
fontawesome.library.add(faCloud);
fontawesome.library.add(faCogs);
fontawesome.library.add(faComments);
fontawesome.library.add(faDna);
fontawesome.library.add(faDownload);
fontawesome.library.add(faEye);
fontawesome.library.add(faFileArchive);
fontawesome.library.add(faHistory);
fontawesome.library.add(faInfoCircle);
fontawesome.library.add(faLink);
fontawesome.library.add(faLock);
fontawesome.library.add(faLockOpen);
fontawesome.library.add(faMinusCircle);
fontawesome.library.add(faPaintBrush);
fontawesome.library.add(faPaperclip);
fontawesome.library.add(faPencilAlt);
fontawesome.library.add(faPlusCircle);
fontawesome.library.add(faQuestionCircle);
fontawesome.library.add(faSignOutAlt);
fontawesome.library.add(faSyncAlt);
fontawesome.library.add(faTags);
fontawesome.library.add(faTimes);
fontawesome.library.add(faUpload);
fontawesome.library.add(faUser);

// REGULAR
import { faCalendarAlt, faCopy, faFilePdf } from '@fortawesome/fontawesome-free-regular';
fontawesome.library.add(faCalendarAlt);
fontawesome.library.add(faCopy);
fontawesome.library.add(faFilePdf);

// BRANDS
import { faGithub, faTwitter } from '@fortawesome/fontawesome-free-brands';
fontawesome.library.add(faTwitter);
fontawesome.library.add(faGithub);
