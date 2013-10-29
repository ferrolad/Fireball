--page
DROP TABLE IF EXISTS cms1_page;
CREATE TABLE cms1_page (
pageID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
parentID INT(10) DEFAULT 0,
title VARCHAR(255) NOT NULL,
description MEDIUMTEXT,
metaDescription MEDIUMTEXT,
metaKeywords VARCHAR(255),
invisible TINYINT(1) DEFAULT 0,
robots ENUM('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow') NOT NULL DEFAULT 'index,follow',
showOrder INT(10) DEFAULT 0,
isHome INT(1) DEFAULT 0,
showSidebar INT(1) DEFAULT 0,
layoutID INT(10),
menuItem MEDIUMTEXT
);

--content
DROP TABLE IF EXISTS cms1_content;
CREATE TABLE cms1_content(
contentID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
pageID INT(10),
title VARCHAR(255) NOT NULL,
showOrder INT(10) DEFAULT 0,
cssID VARCHAR(255),
cssClasses VARCHAR(255)
);

--section
DROP TABLE IF EXISTS cms1_content_section;
CREATE TABLE cms1_content_section(
sectionID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
contentID INT(10),
sectionTypeID INT(10),
sectionData MEDIUMTEXT,
showOrder INT(10) DEFAULT 0,
cssID VARCHAR (255),
cssClasses VARCHAR(255),
additionalData MEDIUMTEXT DEFAULT NULL
);

--stylesheet
DROP TABLE IF EXISTS cms1_stylesheet;
CREATE TABLE cms1_stylesheet(
sheetID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
less MEDIUMTEXT
);

--layout
DROP TABLE IF EXISTS cms1_layout;
CREATE TABLE cms1_layout(
layoutID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
data MEDIUMTEXT
);

--file
DROP TABLE IF EXISTS cms1_file;
CREATE TABLE cms1_file(
fileID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
filename VARCHAR(255) NOT NULL,
size INT(10) NOT NULL,
type VARCHAR(255) NOT NULL,
downloads INT(10) DEFAULT 0
);

--news
DROP TABLE IF EXISTS cms1_news;
CREATE TABLE cms1_news(
newsID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
userID INT(10),
username VARCHAR(255),
subject VARCHAR(255),
message MEDIUMTEXT,
time INT(10) NOT NULL DEFAULT 0,
languageID INT(10),
clicks INT(10) NOT NULL DEFAULT 0,
comments SMALLINT(5) NOT NULL DEFAULT 0,
enableSmilies TINYINT(1) NOT NULL DEFAULT 1,
enableHtml TINYINT(1) NOT NULL DEFAULT 0,
enableBBCodes TINYINT(1) NOT NULL DEFAULT 1,
isDisabled TINYINT(1) NOT NULL DEFAULT 0,
isDeleted TINYINT(1) NOT NULL DEFAULT 0,
deleteTime INT(10) NOT NULL DEFAULT 0,
lastChangeTime INT(10) NOT NULL DEFAULT 0,
lastEditor VARCHAR (255) NOT NULL DEFAULT '',
lastEditorID INT(10) NOT NULL DEFAULT 0,
ipAddress VARCHAR(39) NOT NULL DEFAULT '',
cumulativeLikes INT(10) NOT NULL DEFAULT 0
);


--news to category
DROP TABLE IF EXISTS cms1_news_to_category;
CREATE TABLE cms1_news_to_category(
categoryID INT(10) NOT NULL,
newsID INT(10) NOT NULL,

PRIMARY KEY (categoryID, newsID)
);

--foreign keys
ALTER TABLE cms1_content ADD FOREIGN KEY (pageID) REFERENCES cms1_page (pageID) ON DELETE CASCADE;
ALTER TABLE cms1_content_section ADD FOREIGN KEY (contentID) REFERENCES cms1_content (contentID) ON DELETE CASCADE;
ALTER TABLE cms1_content_section ADD FOREIGN KEY (sectionTypeID) REFERENCES wcf1_object_type (objectTypeID) ON DELETE CASCADE;
ALTER TABLE cms1_news ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE cms1_news ADD FOREIGN KEY (languageID) REFERENCES wcf1_language (languageID) ON DELETE SET NULL;
ALTER TABLE cms1_news_to_category ADD FOREIGN KEY (categoryID) REFERENCES wcf1_category (categoryID) ON DELETE CASCADE;
ALTER TABLE cms1_news_to_category ADD FOREIGN KEY (newsID) REFERENCES cms1_news (newsID) ON DELETE CASCADE;