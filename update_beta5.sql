ALTER TABLE cms1_page ADD COLUMN isDisabled TINYINT(1) DEFAULT 0 AFTER menuItemID;

ALTER TABLE cms1_content ADD COLUMN isDisabled TINYINT(1) DEFAULT 0 AFTER showOrder;
