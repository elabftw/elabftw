/*
 * In this file we define which icons we import (tree shaking feature) from font-awesome
 * This way we don't import all the icons \o/
 *
 */
'use strict';

const fontawesome = require('@fortawesome/fontawesome');

import { faCogs, faDownload, faFileArchive, faInfoCircle, faLock, faLockOpen, faQuestionCircle, faSignOutAlt, faTags } from '@fortawesome/fontawesome-free-solid';
import { faCalendarAlt, faCopy, faFilePdf } from '@fortawesome/fontawesome-free-regular';
import { faGithub, faTwitter } from '@fortawesome/fontawesome-free-brands';

// SOLID
fontawesome.library.add(faCogs);
fontawesome.library.add(faDownload);
fontawesome.library.add(faFileArchive);
fontawesome.library.add(faInfoCircle);
fontawesome.library.add(faLock);
fontawesome.library.add(faLockOpen);
fontawesome.library.add(faQuestionCircle);
fontawesome.library.add(faSignOutAlt);
fontawesome.library.add(faTags);

// REGULAR
fontawesome.library.add(faCalendarAlt);
fontawesome.library.add(faCopy);
fontawesome.library.add(faFilePdf);

// BRANDS
fontawesome.library.add(faTwitter);
fontawesome.library.add(faGithub);
