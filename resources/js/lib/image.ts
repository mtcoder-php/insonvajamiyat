/**
 * Profil rasmini yuklashdan oldin brauzerda tayyorlash:
 * markazdan kvadrat qilib kesish va 512×512 gacha kichraytirish (WEBP).
 * Server baribir tekshiradi (AvatarRequest), bu — tezlik va trafik uchun.
 */
export const AVATAR_SIZE = 512;
export const AVATAR_MIN = 96;
export const AVATAR_MAX_BYTES = 8 * 1024 * 1024; // brauzerga beriladigan asl fayl chegarasi

export class AvatarError extends Error {}

function loadImage(file: File): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new AvatarError("Rasmni o'qib bo'lmadi."));
        };
        image.src = url;
    });
}

export async function prepareAvatar(file: File): Promise<File> {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        throw new AvatarError('Faqat JPG, PNG yoki WEBP rasm yuklang.');
    }

    if (file.size > AVATAR_MAX_BYTES) {
        throw new AvatarError('Rasm hajmi juda katta (8 MB gacha).');
    }

    const image = await loadImage(file);
    const side = Math.min(image.naturalWidth, image.naturalHeight);

    if (side < AVATAR_MIN) {
        throw new AvatarError(
            `Rasm kamida ${AVATAR_MIN}×${AVATAR_MIN} piksel bo'lishi kerak.`,
        );
    }

    const size = Math.min(side, AVATAR_SIZE);
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;

    const context = canvas.getContext('2d');

    if (!context) {
        return file;
    }

    context.imageSmoothingQuality = 'high';
    context.drawImage(
        image,
        (image.naturalWidth - side) / 2,
        (image.naturalHeight - side) / 2,
        side,
        side,
        0,
        0,
        size,
        size,
    );

    const blob = await new Promise<Blob | null>((resolve) =>
        canvas.toBlob(resolve, 'image/webp', 0.9),
    );

    if (!blob) {
        return file;
    }

    const extension = blob.type === 'image/webp' ? 'webp' : 'png';

    return new File([blob], `avatar.${extension}`, { type: blob.type });
}
