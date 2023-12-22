/**
 * interface定義用ファイル
 */

interface ModeProperties {
	mode: string;
	icon: string;
	name?: string[];
};

export interface ModeProp {
	[key: string]: ModeProperties;
};

export interface SftpUploadData {
	uploading: boolean;
	modeProp: ModeProp;
	action: string;
	files: File[];
	fileResetCount: number;
};