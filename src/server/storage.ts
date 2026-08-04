import { createReadStream } from "node:fs";
import { mkdir, writeFile, stat } from "node:fs/promises";
import path from "node:path";
import { randomToken } from "@/lib/crypto";

/** Abstrait le stockage pour pouvoir passer du disque local à un blob distant sans toucher aux appelants. */
export interface StorageProvider {
  put(buffer: Buffer, originalName: string): Promise<string>;
  read(storageKey: string): ReturnType<typeof createReadStream>;
  size(storageKey: string): Promise<number>;
}

const UPLOAD_ROOT = path.join(process.cwd(), "uploads");

function resolveKey(storageKey: string): string {
  const target = path.resolve(UPLOAD_ROOT, storageKey);
  // Empêche un storageKey forgé ("../../etc/passwd") de sortir du dossier d'upload.
  if (!target.startsWith(path.resolve(UPLOAD_ROOT) + path.sep)) {
    throw new Error("Invalid storage key");
  }
  return target;
}

export const storage: StorageProvider = {
  async put(buffer, originalName) {
    const ext = path.extname(originalName).slice(0, 10).replace(/[^a-zA-Z0-9.]/g, "");
    const key = `${randomToken(16)}${ext}`;
    const target = resolveKey(key);
    await mkdir(path.dirname(target), { recursive: true });
    await writeFile(target, buffer);
    return key;
  },
  read(storageKey) {
    return createReadStream(resolveKey(storageKey));
  },
  async size(storageKey) {
    const info = await stat(resolveKey(storageKey));
    return info.size;
  },
};
