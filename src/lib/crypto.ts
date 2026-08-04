import { createHmac, randomBytes, timingSafeEqual } from "node:crypto";

function secret(): string {
  const value = process.env.APP_SECRET;
  if (!value) throw new Error("APP_SECRET is not set");
  return value;
}

export function hmac(payload: string): string {
  return createHmac("sha256", secret()).update(payload).digest("hex");
}

/** Comparison that does not leak how many characters matched. */
export function safeEqual(a: string, b: string): boolean {
  const bufA = Buffer.from(a);
  const bufB = Buffer.from(b);
  if (bufA.length !== bufB.length) return false;
  return timingSafeEqual(bufA, bufB);
}

export function randomToken(bytes = 24): string {
  return randomBytes(bytes).toString("base64url");
}

/**
 * Slug court et lisible pour un QR. Alphabet sans caractères ambigus (0/O, 1/I/l)
 * car ces codes sont parfois recopiés à la main depuis une affiche.
 */
export function randomSlug(length = 10): string {
  const alphabet = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
  const bytes = randomBytes(length);
  let out = "";
  for (let i = 0; i < length; i += 1) out += alphabet[bytes[i] % alphabet.length];
  return out;
}
