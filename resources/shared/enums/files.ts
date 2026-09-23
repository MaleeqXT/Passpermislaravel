export enum FileType {
    PNG = 'image/png',
    WEBP = 'image/webp',
    JPEG = 'image/jpeg',
    JPG = 'image/jpg',
    PDF = 'application/pdf',
    DOC = 'application/doc',
    DOCX = 'application/docx',
    MP4 = 'video/mp4',
    WEBM = 'video/webm',
    OGG_VIDEO = 'video/ogg',
    MPEG_AUDIO = 'audio/mpeg',
    OGG_AUDIO = 'audio/ogg',
    WAV = 'audio/wav',
}

export const fileTypeGroups = {
    all: [FileType.PNG, FileType.WEBP, FileType.JPEG, FileType.JPG, FileType.PDF, FileType.DOC, FileType.DOCX],
    docs: [FileType.PDF, FileType.DOC, FileType.DOCX],
    images: [FileType.PNG, FileType.JPEG, FileType.JPG, FileType.WEBP],
    videos: [FileType.MP4, FileType.WEBM, FileType.OGG_VIDEO],
    audios: [FileType.MPEG_AUDIO, FileType.OGG_AUDIO, FileType.WAV],
};
