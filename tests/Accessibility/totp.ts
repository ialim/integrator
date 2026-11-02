import { createHmac } from 'node:crypto';

/* RFC 6238 time-based codes, as an authenticator app shows them (SHA-1, 30 seconds, 6 digits). */

function base32Decode(input: string): Buffer {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    const clean = input.replace(/[\s=]/g, '').toUpperCase();
    let bits = '';

    for (const char of clean) {
        const value = alphabet.indexOf(char);

        if (value === -1) {
            throw new Error(`Not a base32 secret: ${input}`);
        }

        bits += value.toString(2).padStart(5, '0');
    }

    const bytes: number[] = [];

    for (let i = 0; i + 8 <= bits.length; i += 8) {
        bytes.push(parseInt(bits.slice(i, i + 8), 2));
    }

    return Buffer.from(bytes);
}

export function totp(secret: string, at: number = Date.now()): string {
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(at / 1000 / 30)));

    const hmac = createHmac('sha1', base32Decode(secret)).update(counter).digest();
    const offset = (hmac[hmac.length - 1] ?? 0) & 0x0f;
    const code = (hmac.readUInt32BE(offset) & 0x7fffffff) % 1_000_000;

    return code.toString().padStart(6, '0');
}
