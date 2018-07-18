<?php

/**
 * Strings for component 'tool_updatewizard', language 'en', branch 'MOODLE_31_STABLE'
 *
 * @package tool_updatewizard
 */

$string['pluginname']   = 'Update Wizard';
$string['updatewizard'] = 'Update Wizard';
$string['daily_update'] = 'Daily Wizard - Update Courses, Users & Enrolments';
$string['cachedef_update_wizard'] = 'Update Wizard Caching';

$string['allow_deletes'] = 'Allow deletes';
$string['allow_deletes_help'] = 'Whether the delete field is accepted or not.';
$string['allow_renames'] = 'Allow renames';
$string['allow_renames_help'] = 'Whether the rename field is accepted or not.';
$string['allow_resets'] = 'Allow resets';
$string['allow_resets_help'] = 'Whether the reset field is accepted or not.';
$string['allow_suspends'] = 'Allow suspending and activating of accounts';
$string['assigned_sys_role'] = 'Assigned system role {$a}';
$string['cannot_delete_course_not_exist'] = 'Cannot delete a course that does not exist';
$string['cannot_delete_user_not_exist'] = 'Cannot delete a user that does not exist';
$string['cannot_generate_shortname_update_mode'] = 'Cannot generate a shortname when updates are allowed';
$string['cannot_read_backup_file'] = 'Cannot read the backup file';
$string['cannot_rename_course_not_exist'] = 'Cannot rename a course that does not exist';
$string['cannot_rename_idnumber_conflict'] = 'Cannot rename the course, the ID number conflicts with an existing course';
$string['cannot_rename_shortname_already_in_use'] = 'Cannot rename the course, the shortname is already used';
$string['cannot_rename_user_not_exist'] = 'Cannot rename a user that does not exist';
$string['cannot_rename_username_already_in_use'] = 'Cannot rename the course, the shortname is already used';
$string['cannot_update_frontpage'] = 'It is forbidden to modify the front page';
$string['can_only_rename_in_update_mode'] = 'Can only rename a course when update is allowed';
$string['can_only_reset_course_in_update_mode'] = 'Can only reset a course in update mode';
$string['could_not_resolve_category_by_id'] = 'Could not resolve category by ID';
$string['could_not_resolve_category_by_idnumber'] = 'Could not resolve category by ID number';
$string['could_not_resolve_category_by_path'] = 'Could not resolve category by path';
$string['course_created'] = 'Course created';
$string['course_deleted'] = 'Course deleted';
$string['course_deletion_not_allowed'] = 'Course deletion is not allowed';
$string['course_does_not_exist_and_create_not_allowed'] = 'The course does not exist and creating course is not allowed';
$string['course_exists_and_upload_not_allowed'] = 'The course exists and update is not allowed';
$string['course_file'] = 'File';
$string['course_file_help'] = 'This file must be a CSV file.';
$string['course_idnumber_incremented'] = 'Course ID number incremented {$a->from} -> {$a->to}';
$string['course_process'] = 'Course process';
$string['course_renamed'] = 'Course renamed';
$string['course_renaming_not_allowed'] = 'Course renaming is not allowed';
$string['course_reset'] = 'Course reset';
$string['course_reset_not_allowed'] = 'Course reset now allowed';
$string['course_restored'] = 'Course restored';
$string['courses_total'] = 'Courses total: {$a}';
$string['courses_created'] = 'Courses created: {$a}';
$string['courses_updated'] = 'Courses updated: {$a}';
$string['courses_deleted'] = 'Courses deleted: {$a}';
$string['courses_errors'] = 'Courses errors: {$a}';
$string['course_shortname_incremented'] = 'Course shortname incremented {$a->from} -> {$a->to}';
$string['course_shortname_generated'] = 'Course shortname generated: {$a}';
$string['course_template_name'] = 'Restore from this course after upload';
$string['course_template_name_help'] = 'Enter an existing course shortname to use as a template for the creation of all courses.';
$string['course_to_restore_from_does_not_exist'] = 'The course to restore from does not exist';
$string['course_updated'] = 'Course updated';
$string['create_all'] = 'Create all, increment shortname if needed';
$string['create_new'] = 'Create new courses only, skip existing ones';
$string['create_or_update'] = 'Create new courses, or update existing ones';
$string['csv_delimiter'] = 'CSV delimiter';
$string['csv_delimiter_help'] = 'CSV delimiter of the CSV file.';
$string['csv_file_error'] = 'There is something wrong with the format of the CSV file. Please check the number of headings and columns match, and that the delimiter and file encoding are correct: {$a}';
$string['csv_line'] = 'Line';
$string['default_values'] = 'Default values';
$string['delete_errors'] = 'Delete errors';
$string['encoding'] = 'Encoding';
$string['encoding_help'] = 'Encoding of the CSV file.';
$string['error_mnet_add'] = 'Can not add remote users';
$string['error_while_restoring_course'] = 'Error while restoring the course';
$string['error_while_deleting_course'] = 'Error while deleting the course';
$string['error_while_deleting_user'] = 'Error while deleting the user';
$string['errors'] = 'Errors';
$string['generated_shortname_invalid'] = 'The generated shortname is invalid';
$string['generated_shortname_already_in_use'] = 'The generated shortname is already in use';
$string['id'] = 'ID';
$string['import_options'] = 'Import options';
$string['idnumber_already_in_use'] = 'ID number already used by a course';
$string['invalid_backup_file'] = 'Invalid backup file';
$string['invalid_course_format'] = 'Invalid course format';
$string['invalid_csv_file'] = 'Invalid input CSV file';
$string['invalid_encoding'] = 'Invalid encoding';
$string['invalid_mode'] = 'Invalid mode selected';
$string['invalid_update_mode'] = 'Invalid update mode selected';
$string['invalid_roles'] = 'Invalid role names: {$a}';
$string['invalid_shortname'] = 'Invalid shortname';
$string['invalid_username'] = 'Invalid username';
$string['missing_mandatory_fields'] = 'Missing value for mandatory fields: {$a}';
$string['missing_shortname_no_template'] = 'Missing shortname and shortname template not set';
$string['mode'] = 'Upload mode';
$string['mode_help'] = 'This allows you to specify if courses can be created and/or updated.';
$string['no_changes'] = 'No changes';
$string['preview'] = 'Preview';
$string['rename_errors'] = 'Rename errors';
$string['required_template'] = 'Required. You may use template syntax here (%l = lastname, %f = firstname, %u = username). See help for details and examples.';
$string['reset'] = 'Reset course after upload';
$string['reset_help'] = 'Whether to reset the course after creating/updating it.';
$string['result'] = 'Result';
$string['restore_after_import'] = 'Restore after import';
$string['row_preview_num'] = 'Preview rows';
$string['row_preview_num_help'] = 'Number of rows from the CSV file that will be previewed in the next page. This option exists in order to limit the next page size.';
$string['shortname_template'] = 'Template to generate a shortname';
$string['shortname_template_help'] = 'The short name of the course is displayed in the navigation. You may use template syntax here (%f = fullname, %i = idnumber), or enter an initial value that is incremented.';
$string['template_file'] = 'Restore from this file after upload';
$string['template_file_help'] = 'Select a file to use as a template for the creation of all courses.';
$string['unassigned_sys_role'] = 'Unassigned system role {$a}';
$string['unknown_import_mode'] = 'Unknown import mode';
$string['update_missing'] = 'Fill in missing items from CSV data and defaults';
$string['updat_emode'] = 'Update mode';
$string['update_mode_help'] = 'If you allow courses to be updated, you also have to tell the tool what to update the courses with.';
$string['update_mode_does_set_to_nothing'] = 'Update mode does not allow anything to be updated';
$string['update_only'] = 'Only update existing courses';
$string['update_with_data_or_defaults'] = 'Update with CSV data and defaults';
$string['update_with_data_only'] = 'Update with CSV data only';
$string['upload_courses'] = 'Upload courses';
$string['upload_courses_help'] = 'Courses may be uploaded via text file. The format of the file should be as follows:

* Each line of the file contains one record
* Each record is a series of data separated by commas (or other delimiters)
* The first record contains a list of fieldnames defining the format of the rest of the file
* Required fieldnames are shortname, fullname, and category';
$string['upload_courses_preview'] = 'Upload courses preview';
$string['upload_courses_result'] = 'Upload courses results';
$string['upload_picture_bad_user_field'] = 'The user attribute specified is not valid. Please, try again.';
$string['upload_picture_cannot_move_zip'] = 'Cannot move zip file to temporary directory.';
$string['upload_picture_cannot_process_dir'] = 'Cannot process unzipped files.';
$string['upload_picture_cannot_save'] = 'Cannot save picture for user {$a}. Check original picture file.';
$string['upload_picture_cannot_unzip'] = 'Cannot unzip pictures file.';
$string['upload_picture_invalid_filename'] = 'Picture file {$a} has invalid characters in its name. Skipping.';
$string['upload_picture_overwrite'] = 'Overwrite existing user pictures?';
$string['upload_picture_user_field'] = 'User attribute to use to match pictures:';
$string['upload_picture_user_not_found'] = 'User with a \'{$a->userfield}\' value of \'{$a->uservalue}\' does not exist. Skipping.';
$string['upload_picture_user_skipped'] = 'Skipping user {$a} (already has a picture).';
$string['upload_picture_user_updated'] = 'Picture updated for user {$a}.';
$string['upload_pictures'] = 'Upload user pictures';
$string['upload_pictures_help'] = 'User pictures can be uploaded as a zip file of image files. The image files should be named chosen-user-attribute.extension, for example user1234.jpg for a user with username user1234.';
$string['upload_users'] = 'Upload users';
$string['upload_users_help'] = 'Users may be uploaded (and optionally enrolled in courses) via text file. The format of the file should be as follows:

* Each line of the file contains one record
* Each record is a series of data separated by commas (or other delimiters)
* The first record contains a list of fieldnames defining the format of the rest of the file
* Required fieldnames are username, password, firstname, lastname, email';
$string['upload_users_preview'] = 'Upload users preview';
$string['upload_users_result'] = 'Upload users results';
$string['upload_user:upload_user_pictures'] = 'Upload user pictures';
$string['user_account_updated'] = 'User updated';
$string['user_account_up_to_date'] = 'User up-to-date';
$string['user_deleted'] = 'User deleted';
$string['user_renamed'] = 'User renamed';
$string['users_created'] = 'Users created';
$string['users_deleted'] = 'Users deleted';
$string['users_renamed'] = 'Users renamed';
$string['users_skipped'] = 'Users skipped';
$string['users_updated'] = 'Users updated';
$string['users_weak_password'] = 'Users having a weak password';
$string['upload_user_bulk'] = 'Select for bulk user actions';
$string['upload_user_bulk_all'] = 'All users';
$string['upload_user_bulk_new'] = 'New users';
$string['upload_user_bulk_updated'] = 'Updated users';
$string['upload_user_csv_line'] = 'CSV line';
$string['upload_user_legacy1_role'] = '(Original Student) typeN=1';
$string['upload_user_legacy2_role'] = '(Original Teacher) typeN=2';
$string['upload_user_legacy3_role'] = '(Original Non-editing teacher) typeN=3';
$string['upload_user_no_email_duplicates'] = 'Prevent email address duplicates';
$string['upload_user_op_type'] = 'Upload type';
$string['upload_user_op_type_add_inc'] = 'Add all, append number to usernames if needed';
$string['upload_user_op_type_add_new'] = 'Add new only, skip existing users';
$string['upload_user_op_type_add_update'] = 'Add new and update existing users';
$string['upload_user_op_type_update'] = 'Update existing users only';
$string['upload_user_password_cron'] = 'Generated in cron';
$string['upload_user_password_new'] = 'New user password';
$string['upload_user_password_old'] = 'Existing user password';
$string['upload_user_standard_usernames'] = 'Standardise usernames';
$string['upload_user_update_all'] = 'Override with file and defaults';
$string['upload_user_update_from_file'] = 'Override with file';
$string['upload_user_update_missing'] = 'Fill in missing from file and defaults';
$string['upload_user_update_type'] = 'Existing user details';
$string['upload_user_username_template'] = 'Username template';
$string['user_created'] = 'User created';
$string['user_deleted'] = 'User deleted';
$string['user_deletion_not_allowed'] = 'User deletion is not allowed';
$string['user_does_not_exist_and_create_not_allowed'] = 'The user does not exist and creating user is not allowed';
$string['user_exists_and_upload_not_allowed'] = 'The user exists and update is not allowed';
$string['user_renamed'] = 'User renamed';
$string['user_updated'] = 'User updated';


$string['expiredaction'] = 'Enrolment expiration action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['filelockedmail'] = 'The text file you are using for file-based enrolments ({$a}) can not be deleted by the cron process.  This usually means the permissions are wrong on it.  Please fix the permissions so that Moodle can delete the file, otherwise it might be processed repeatedly.';
$string['filelockedmailsubject'] = 'Important error: Enrolment file';
$string['flatfile:manage'] = 'Manage user enrolments manually';
$string['flatfile:unenrol'] = 'Unenrol users from the course manually';
$string['location'] = 'File location';
$string['location_desc'] = 'Specify full path to the enrolment file. The file is automatically deleted after processing.';
$string['notifyadmin'] = 'Notify administrator';
$string['notifyenrolled'] = 'Notify enrolled users';
$string['notifyenroller'] = 'Notify user responsible for enrolments';
$string['messageprovider:flatfile_enrolment'] = 'Flat file enrolment messages';
$string['mapping'] = 'Flat file role mapping';
$string['pluginname_desc'] = 'This method will repeatedly check for and process a specially-formatted text file in the location that you specify.
The file is a comma separated file assumed to have four or six fields per line:

    operation, role, user idnumber, course idnumber [, starttime [, endtime]]

where:

* operation - add | del
* role - student | teacher | teacheredit
* user idnumber - idnumber in the user table NB not id
* course idnumber - idnumber in the course table NB not id
* starttime - start time (in seconds since epoch) - optional
* endtime - end time (in seconds since epoch) - optional

It could look something like this:
<pre class="informationbox">
   add, student, 5, CF101
   add, teacher, 6, CF101
   add, teacheredit, 7, CF101
   del, student, 8, CF101
   del, student, 17, CF101
   add, student, 21, CF101, 1091115000, 1091215000
</pre>';
