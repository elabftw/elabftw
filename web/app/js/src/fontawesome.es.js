/*
 * In this file we define which icons we import (tree shaking feature) from font-awesome
 * This way we don't import all the icons \o/
 *
 */
'use strict';

const fontawesome = require('@fortawesome/fontawesome');


// SOLID
import {
    faBold,
    faChartPie,
    faCheck,
    faCheckSquare,
    faChevronCircleLeft,
    faChevronRight,
    faClipboardCheck,
    faClone,
    faCloud,
    faCode,
    faCogs,
    faComments,
    faDna,
    faDownload,
    faExclamationTriangle,
    faExpand,
    faEye,
    faFile,
    faFileArchive,
    faFileCode,
    faFileExcel,
    faFilePdf,
    faFilePowerpoint,
    faFileVideo,
    faFileWord,
    faHeading,
    faHistory,
    faImage,
    faItalic,
    faInfoCircle,
    faLink,
    faList,
    faListOl,
    faLock,
    faLockOpen,
    faMinusCircle,
    faPaintBrush,
    faPaperclip,
    faPencilAlt,
    faPlusCircle,
    faQuestionCircle,
    faQuoteLeft,
    faSearch,
    faSignOutAlt,
    faSquare,
    faStar,
    faSyncAlt,
    faTags,
    faTimes,
    faUpload,
    faUser,
} from '@fortawesome/fontawesome-free-solid';

fontawesome.library.add(faBold);
fontawesome.library.add(faChartPie);
fontawesome.library.add(faCheck);
fontawesome.library.add(faCheckSquare);
fontawesome.library.add(faChevronCircleLeft);
fontawesome.library.add(faChevronRight);
fontawesome.library.add(faClipboardCheck);
fontawesome.library.add(faClone);
fontawesome.library.add(faCloud);
fontawesome.library.add(faCode);
fontawesome.library.add(faCogs);
fontawesome.library.add(faComments);
fontawesome.library.add(faDna);
fontawesome.library.add(faDownload);
fontawesome.library.add(faExclamationTriangle);
fontawesome.library.add(faExpand);
fontawesome.library.add(faEye);
fontawesome.library.add(faFile);
fontawesome.library.add(faFileArchive);
fontawesome.library.add(faFileCode);
fontawesome.library.add(faFileExcel);
fontawesome.library.add(faFilePdf);
fontawesome.library.add(faFilePowerpoint);
fontawesome.library.add(faFileVideo);
fontawesome.library.add(faFileWord);
fontawesome.library.add(faHeading);
fontawesome.library.add(faHistory);
fontawesome.library.add(faImage);
fontawesome.library.add(faItalic);
fontawesome.library.add(faInfoCircle);
fontawesome.library.add(faLink);
fontawesome.library.add(faList);
fontawesome.library.add(faListOl);
fontawesome.library.add(faLock);
fontawesome.library.add(faLockOpen);
fontawesome.library.add(faMinusCircle);
fontawesome.library.add(faPaintBrush);
fontawesome.library.add(faPaperclip);
fontawesome.library.add(faPencilAlt);
fontawesome.library.add(faPlusCircle);
fontawesome.library.add(faQuestionCircle);
fontawesome.library.add(faQuoteLeft);
fontawesome.library.add(faSearch);
fontawesome.library.add(faSignOutAlt);
fontawesome.library.add(faSquare);
fontawesome.library.add(faStar);
fontawesome.library.add(faSyncAlt);
fontawesome.library.add(faTags);
fontawesome.library.add(faTimes);
fontawesome.library.add(faUpload);
fontawesome.library.add(faUser);

// REGULAR
import { faCalendarAlt, faCalendarCheck, faCopy} from '@fortawesome/fontawesome-free-regular';
fontawesome.library.add(faCalendarAlt);
fontawesome.library.add(faCalendarCheck);
fontawesome.library.add(faCopy);

// BRANDS
import { faGithub, faTwitter } from '@fortawesome/fontawesome-free-brands';
fontawesome.library.add(faTwitter);
fontawesome.library.add(faGithub);
