import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { storage } from "@/server/storage";
import { providers } from "@/server/providers";
import { AttachmentKind } from "@/generated/prisma/enums";

const MAX_BYTES = 10 * 1024 * 1024;
const ALLOWED_PREFIXES = ["image/", "audio/"];
const ALLOWED_EXACT = ["application/pdf"];

export async function POST(request: Request) {
  const form = await request.formData();
  const file = form.get("file");
  const draftId = form.get("draftId");
  const requestedKind = String(form.get("kind") ?? "AUTRE");

  if (!(file instanceof File) || typeof draftId !== "string" || !draftId) {
    return NextResponse.json({ error: "invalid_request" }, { status: 400 });
  }
  if (file.size > MAX_BYTES) {
    return NextResponse.json({ error: "too_large" }, { status: 413 });
  }

  const mimeType = file.type || "application/octet-stream";
  const allowed =
    ALLOWED_PREFIXES.some((prefix) => mimeType.startsWith(prefix)) ||
    ALLOWED_EXACT.includes(mimeType);
  if (!allowed) {
    return NextResponse.json({ error: "unsupported_type" }, { status: 415 });
  }

  const kind: AttachmentKind = mimeType.startsWith("audio/")
    ? "AUDIO"
    : requestedKind in AttachmentKind
      ? (requestedKind as AttachmentKind)
      : "AUTRE";

  const buffer = Buffer.from(await file.arrayBuffer());
  const storageKey = await storage.put(buffer, file.name);
  const scanStatus = await providers.antivirus.scan(storageKey);

  const attachment = await prisma.attachment.create({
    data: {
      draftId,
      kind,
      storageKey,
      originalName: file.name.slice(0, 200),
      mimeType,
      size: buffer.byteLength,
      scanStatus,
    },
  });

  return NextResponse.json({
    id: attachment.id,
    kind: attachment.kind,
    originalName: attachment.originalName,
    mimeType: attachment.mimeType,
  });
}
