<?php

class PeepSoTagsAjax implements PeepSoAjaxCallback
{
	private static $_instance = NULL;

	private function __construct() {}

	/*
	 * retrieve singleton class instance
	 * @return instance reference to plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return (self::$_instance);
	}


	/**
	 * Get `taggable` users based on the Friends add-on or users that have commented on a post.
	 * Returns a json string in the following format
	 */
	public function get_taggable(PeepSoAjaxResponse $resp)
	{
		// { id:1, name:'Daniel Zahariev',  'img':'http://example.com/img1.jpg', 'type':'user' }
		$user_id = PeepSo::get_user_id();

		$profile = PeepSoActivity::get_instance();
		$profile->set_user_id($user_id);

		$taggable = array();
 		$current_user = new PeepSoUser($user_id);

		// Get friends if available
		if (class_exists('PeepSoFriendsPlugin')) {
			$peepso_friends = PeepSoFriends::get_instance();

			while ($friend = $peepso_friends->get_next_friend($user_id)) {
				if (!$friend->is_accessible('profile'))
					continue;

				$taggable[$friend->get_id()] = array(
					'id' => $friend->get_id(),
					'name' => $friend->get_display_name(),
					'avatar' => $friend->get_avatar(),
					'icon' => $friend->get_avatar(),
					'type' => 'friend'
				);
			}
		}

		$input = new PeepSoInput();

		$act_id = $input->get_int('act_id', NULL);

		if (!is_null($act_id) && FALSE === is_null($activity = $profile->get_activity_post($act_id))) {
			// add author as default
			$author = new PeepSoUser($activity->post_author);

			$taggable[$author->get_id()] = array(
				'id' => $author->get_id(),
				'name' => $author->get_display_name(),
				'avatar' => $author->get_avatar(),
				'icon' => $author->get_avatar(),
				'type' => 'author'
			);

			$users = $profile->get_comment_users($activity->act_external_id, $activity->act_module_id);

			while ($users->have_posts()) {
				$users->next_post();

				// skip if user was already found
				if (isset($taggable[$users->post->post_author]))
					continue;

				$user = new PeepSoUser($users->post->post_author);

				if (!$user->is_accessible('profile'))
					continue;

				$taggable[$user->get_id()] = array(
					'id' => $user->get_id(),
					'name' => $user->get_display_name(),
					'avatar' => $user->get_avatar(),
					'icon' => $user->get_avatar(),
					'type' => 'friend'
				);
			}
		}

		$resp->success(TRUE);
		$resp->set('users', $taggable);
	}
}