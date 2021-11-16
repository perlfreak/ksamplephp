CREATE DATABASE ksamplephp;


CREATE USER 'dbuser'@'localhost' IDENTIFIED BY '@P0o9i8u7';
GRANT ALL PRIVILEGES ON `ksamplephp`.* TO 'dbuser'@'localhost';
FLUSH PRIVILEGES;


USE ksamplephp;


CREATE TABLE IF NOT EXISTS role_permission (
  role_id varchar(128) NOT NULL,
  permission_id varchar(128) NOT NULL,
  PRIMARY KEY(role_id, permission_id)
);


CREATE TABLE IF NOT EXISTS user_role (
  user_id varchar(128) NOT NULL,
  role_id varchar(128) NOT NULL,
  create_user varchar(128),
  create_date datetime DEFAULT CURRENT_TIMESTAMP,
  update_user varchar(128),
  update_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(user_id, role_id)
);


CREATE TABLE IF NOT EXISTS permissions (
  permission_id varchar(128) NOT NULL,
  permission_name varchar(256),
  description varchar(512),
  PRIMARY KEY(permission_id)
);


CREATE TABLE IF NOT EXISTS roles (
  role_id varchar(128) NOT NULL,
  role_name varchar(256),
  description varchar(512),
  PRIMARY KEY(role_id)
);


CREATE TABLE IF NOT EXISTS users (
  user_id varchar(128) NOT NULL,
  password varchar(64) NOT NULL,
  firstname varchar(64),
  lastname varchar(64),
  email varchar(256),
  locale varchar(16) NOT NULL,
  remind_pw_flg tinyint DEFAULT 0,
  del_flg tinyint DEFAULT 0,
  login_failure_date datetime,
  login_failure_count tinyint DEFAULT 0,
  create_user varchar(128),
  create_date datetime DEFAULT CURRENT_TIMESTAMP,
  update_user varchar(128),
  update_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(user_id)
);


/*
 * Default password of admin is "Passwd1234".
 */
INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES ('sysadmin', 'System Administrator', 'System Administrator');
INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES ('user', 'User', 'User');
INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`) VALUES ('chagne_password', 'Change password', 'Change passwor');
INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`) VALUES ('change_locale', 'Change locale', 'Change locale');
INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`) VALUES ('manage_users', 'User information management', 'User information management');
INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES ('sysadmin', 'change_password');
INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES ('sysadmin', 'change_locale');
INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES ('sysadmin', 'manage_users');
INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES ('user', 'change_password');
INSERT INTO `users` (`user_id`, `password`, `firstname`, `lastname`, `email`, `locale`, `create_user`, `update_user`) VALUES ('admin', '$2y$10$jc75knO1rIV/O.AfVIWF0.zyAmUIQyO/2LWMvN.XhrhLqvBuHCPO6', 'Administrator', 'System', NULL, 'ja', 'admin', 'admin');
INSERT INTO `user_role` (`user_id`, `role_id`, `create_user`, `update_user`) VALUES ('admin', 'sysadmin', 'admin', 'admin');
