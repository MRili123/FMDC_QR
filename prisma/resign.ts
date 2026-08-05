import "dotenv/config";
import { PrismaMariaDb } from "@prisma/adapter-mariadb";
import { createHmac } from "node:crypto";
import { PrismaClient } from "../src/generated/prisma/client";

/**
 * Recalcule les signatures HMAC de tous les QR. À lancer après une rotation
 * d'APP_SECRET, sinon chaque scan afficherait l'avertissement anti-quishing.
 */
const prisma = new PrismaClient({
  adapter: new PrismaMariaDb(process.env.DATABASE_URL!),
});

async function main() {
  const codes = await prisma.qrCode.findMany();
  for (const qr of codes) {
    const signature = createHmac("sha256", process.env.APP_SECRET ?? "dev")
      .update(qr.code)
      .digest("hex");
    if (signature !== qr.signature) {
      await prisma.qrCode.update({ where: { id: qr.id }, data: { signature } });
      console.info(`Signature régénérée pour ${qr.code}`);
    }
  }
  console.info(`${codes.length} QR vérifiés.`);
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
