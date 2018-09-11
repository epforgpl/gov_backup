# Create user to read the data from database
CREATE USER 'govbackup_readonly'@'%' IDENTIFIED BY 'PASS';

GRANT SELECT ON epf.web_objects TO 'govbackup_readonly'@'%';
GRANT SELECT ON epf.web_objects_revisions TO 'govbackup_readonly'@'%';
GRANT SELECT ON epf.web_objects_versions TO 'govbackup_readonly'@'%';
GRANT SELECT ON epf.web_portals TO 'govbackup_readonly'@'%';