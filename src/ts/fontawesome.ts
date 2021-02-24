/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * In this file we define which icons we import (tree shaking feature) from font-awesome
 * This way we don't import all the icons \o/
 */

// CORE
import { library, dom } from '@fortawesome/fontawesome-svg-core';

// SOLID
import {
  faBold,
  faBug,
  faCalendarPlus,
  faChartPie,
  faCheck,
  faCheckSquare,
  faChevronCircleLeft,
  faChevronLeft,
  faChevronRight,
  faClipboardCheck,
  faClone,
  faCloud,
  faCode,
  faCogs,
  faComments,
  faDna,
  faDownload,
  faEllipsisH,
  faEnvelope,
  faExclamationTriangle,
  faEye,
  faEyeSlash,
  faFile,
  faFileArchive,
  faFileCode,
  faFileExcel,
  faFileImage,
  faFileImport,
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
  faShareAlt,
  faSignOutAlt,
  faSort,
  faSquare,
  faStar,
  faSyncAlt,
  faTags,
  faThumbtack,
  faTimes,
  faTools,
  faTrashAlt,
  faUpload,
  faUser,
  faUserCircle,
  faUsers,
} from '@fortawesome/free-solid-svg-icons';

library.add(
  faBold,
  faBug,
  faCalendarPlus,
  faChartPie,
  faCheck,
  faCheckSquare,
  faChevronCircleLeft,
  faChevronLeft,
  faChevronRight,
  faClipboardCheck,
  faClone,
  faCloud,
  faCode,
  faCogs,
  faComments,
  faDna,
  faDownload,
  faEllipsisH,
  faEnvelope,
  faExclamationTriangle,
  faEye,
  faEyeSlash,
  faFile,
  faFileArchive,
  faFileCode,
  faFileExcel,
  faFileImage,
  faFileImport,
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
  faShareAlt,
  faSignOutAlt,
  faSort,
  faSquare,
  faStar,
  faSyncAlt,
  faTags,
  faThumbtack,
  faTimes,
  faTools,
  faTrashAlt,
  faUpload,
  faUser,
  faUserCircle,
  faUsers
);

// REGULAR
import { faCalendarAlt, faCalendarCheck, faCopy} from '@fortawesome/free-regular-svg-icons';
library.add(faCalendarAlt, faCalendarCheck, faCopy);

// BRANDS
import { faGithub, faGitter, faTwitter } from '@fortawesome/free-brands-svg-icons';
library.add(faGithub, faGitter, faTwitter);

// Kicks off the process of finding <i> tags and replacing with <svg>
dom.watch();
