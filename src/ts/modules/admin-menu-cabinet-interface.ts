/**
 * interface定義用ファイル
 */
export interface cabinetFolder{
	FolderId: string,
	FolderName: string,
	FolderNode: string,
	FolderPath: string,
	FileCount: string,
	FileSize: string,
	TimeStamp: string,
	ParseFolderPath: string[],
	FolderNamePath: string,
}
export interface cabinetImage{
	FileAccessDate:string,
	FileHeight:string,
	FileId:string,
	FileName:string,
	FilePath:string,
	FileSize:string,
	FileType:string,
	FileUrl:string,
	FileWidth:string,
	FolderId:string,
	FolderName:string,
	FolderNode:string,
	FolderPath:string,
	TimeStamp:string,
}