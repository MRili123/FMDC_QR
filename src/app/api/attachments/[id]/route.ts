import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { storage } from "@/server/storage";
import { currentUser, recordAudit } from "@/server/session";
import type { ReadableStream as WebReadableStream } from "node:stream/web";
import { Readable } from "node:stream";

export async function GET(_request: Request, context: RouteContext<"/api/attachments/[id]">) {
  const user = await currentUser();
  if (!user) return NextResponse.json({ error: "unauthorised" }, { status: 401 });

  const { id } = await context.params;
  const attachment = await prisma.attachment.findUnique({
    where: { id },
    include: { dossier: { select: { id: true, assignedAssociationId: true } } },
  });
  if (!attachment?.dossier) return NextResponse.json({ error: "not_found" }, { status: 404 });

  if (
    user.role === "ASSOCIATION_AGENT" &&
    attachment.dossier.assignedAssociationId !== user.associationId
  ) {
    return NextResponse.json({ error: "not_found" }, { status: 404 });
  }

  await recordAudit(user, "attachment.download", "Attachment", attachment.id);

  const stream = Readable.toWeb(storage.read(attachment.storageKey)) as WebReadableStream<Uint8Array>;
  return new NextResponse(stream as unknown as BodyInit, {
    headers: {
      "Content-Type": attachment.mimeType,
      // `inline` laisserait un SVG ou un HTML piégé s'exécuter sur notre origine.
      "Content-Disposition": `attachment; filename="${encodeURIComponent(attachment.originalName)}"`,
      "Content-Length": String(attachment.size),
    },
  });
}
