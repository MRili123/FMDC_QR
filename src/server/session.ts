import { cookies } from "next/headers";
import { SignJWT, jwtVerify } from "jose";
import { prisma } from "@/lib/db";
import type { AdminRole } from "@/generated/prisma/enums";

const COOKIE = "qrconso_session";
const MAX_AGE_SECONDS = 8 * 60 * 60;

export interface SessionUser {
  id: string;
  email: string;
  nom: string;
  role: AdminRole;
  associationId: string | null;
}

function key(): Uint8Array {
  const secret = process.env.APP_SECRET;
  if (!secret) throw new Error("APP_SECRET is not set");
  return new TextEncoder().encode(secret);
}

export async function createSession(userId: string): Promise<void> {
  const token = await new SignJWT({ sub: userId })
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime(`${MAX_AGE_SECONDS}s`)
    .sign(key());

  const store = await cookies();
  store.set(COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: MAX_AGE_SECONDS,
  });
}

export async function destroySession(): Promise<void> {
  const store = await cookies();
  store.delete(COOKIE);
}

/**
 * Relit l'agent en base à chaque requête plutôt que de faire confiance au jeton :
 * désactiver un compte ou changer son association doit prendre effet immédiatement.
 */
export async function currentUser(): Promise<SessionUser | null> {
  const store = await cookies();
  const token = store.get(COOKIE)?.value;
  if (!token) return null;

  try {
    const { payload } = await jwtVerify(token, key());
    if (typeof payload.sub !== "string") return null;

    const user = await prisma.adminUser.findFirst({
      where: { id: payload.sub, active: true },
      select: { id: true, email: true, nom: true, role: true, associationId: true },
    });
    return user;
  } catch {
    return null;
  }
}

export async function recordAudit(
  user: SessionUser,
  action: string,
  entityType: string,
  entityId: string,
): Promise<void> {
  await prisma.auditLog.create({
    data: { actorId: user.id, actorLabel: user.nom, action, entityType, entityId },
  });
}
