<?php
/**
 * OpenAI Assistants API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_OpenAI_Assistants_API' ) ) {
	new N2_OpenAI_Assistants_API();
	return;
}

/**
 * OpenAI Assistants API
 */
class N2_OpenAI_Assistants_API extends N2_OpenAI_Base_API {

	/**
	 * ヘッダー配列の作成
	 */
	protected static function set_header() {
		parent::set_header();
		// AssistantsはBeta版のため独自ヘッダー追加
		static::$data['header']['OpenAI-Beta'] = 'assistants=v1';
	}

	/**
	 * Assistantsを作る関数
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function create_assistants( $usecase = '', $user_message = '' ) {
		echo 'create';
	}

	/**
	 * Filesを作る関数
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function create_files( $usecase = '', $user_message = '' ) {
		echo 'files';
	}

	/**
	 * Threadsを作る関数
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function create_threads( $usecase = '', $user_message = '' ) {
		echo 'threads';
	}

	/**
	 * Code Interpreterを作る関数
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function create_code_interpreter( $usecase = '', $user_message = '' ) {
		echo 'code_interpreter';
	}

	/**
	 * Retrievalを作る関数
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function create_retrieval( $usecase = '', $user_message = '' ) {
		echo 'retrieval';
	}

	/**
	 * Function Callingを作る関数
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function create_function_calling( $usecase = '', $user_message = '' ) {
		echo 'function_calling';
	}
}
