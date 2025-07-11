function BigInteger(t, e, r) {
    null != t && ("number" == typeof t ? this.fromNumber(t, e, r) : null == e && "string" != typeof t ? this.fromString(t, 256) : this.fromString(t, e));
}
function nbi() {
    return new BigInteger(null);
}
function am1(t, e, r, n, i, s) {
    for (; --s >= 0; ) {
        var o = e * this[t++] + r[n] + i;
        (i = Math.floor(o / 67108864)), (r[n++] = 67108863 & o);
    }
    return i;
}
function am2(t, e, r, n, i, s) {
    for (var o = 32767 & e, a = e >> 15; --s >= 0; ) {
        var h = 32767 & this[t],
            u = this[t++] >> 15,
            p = a * h + u * o;
        (h = o * h + ((32767 & p) << 15) + r[n] + (1073741823 & i)), (i = (h >>> 30) + (p >>> 15) + a * u + (i >>> 30)), (r[n++] = 1073741823 & h);
    }
    return i;
}
function am3(t, e, r, n, i, s) {
    for (var o = 16383 & e, a = e >> 14; --s >= 0; ) {
        var h = 16383 & this[t],
            u = this[t++] >> 14,
            p = a * h + u * o;
        (h = o * h + ((16383 & p) << 14) + r[n] + i), (i = (h >> 28) + (p >> 14) + a * u), (r[n++] = 268435455 & h);
    }
    return i;
}
function int2char(t) {
    return BI_RM.charAt(t);
}
function intAt(t, e) {
    var r = BI_RC[t.charCodeAt(e)];
    return null == r ? -1 : r;
}
function bnpCopyTo(t) {
    for (var e = this.t - 1; e >= 0; --e) t[e] = this[e];
    (t.t = this.t), (t.s = this.s);
}
function bnpFromInt(t) {
    (this.t = 1), (this.s = 0 > t ? -1 : 0), t > 0 ? (this[0] = t) : -1 > t ? (this[0] = t + this.DV) : (this.t = 0);
}
function nbv(t) {
    var e = nbi();
    return e.fromInt(t), e;
}
function bnpFromString(t, e) {
    var r;
    if (16 == e) r = 4;
    else if (8 == e) r = 3;
    else if (256 == e) r = 8;
    else if (2 == e) r = 1;
    else if (32 == e) r = 5;
    else {
        if (4 != e) return void this.fromRadix(t, e);
        r = 2;
    }
    (this.t = 0), (this.s = 0);
    for (var n = t.length, i = !1, s = 0; --n >= 0; ) {
        var o = 8 == r ? 255 & t[n] : intAt(t, n);
        0 > o
            ? "-" == t.charAt(n) && (i = !0)
            : ((i = !1),
              0 == s ? (this[this.t++] = o) : s + r > this.DB ? ((this[this.t - 1] |= (o & ((1 << (this.DB - s)) - 1)) << s), (this[this.t++] = o >> (this.DB - s))) : (this[this.t - 1] |= o << s),
              (s += r),
              s >= this.DB && (s -= this.DB));
    }
    8 == r && 0 != (128 & t[0]) && ((this.s = -1), s > 0 && (this[this.t - 1] |= ((1 << (this.DB - s)) - 1) << s)), this.clamp(), i && BigInteger.ZERO.subTo(this, this);
}
function bnpClamp() {
    for (var t = this.s & this.DM; this.t > 0 && this[this.t - 1] == t; ) --this.t;
}
function bnToString(t) {
    if (this.s < 0) return "-" + this.negate().toString(t);
    var e;
    if (16 == t) e = 4;
    else if (8 == t) e = 3;
    else if (2 == t) e = 1;
    else if (32 == t) e = 5;
    else {
        if (4 != t) return this.toRadix(t);
        e = 2;
    }
    var r,
        n = (1 << e) - 1,
        i = !1,
        s = "",
        o = this.t,
        a = this.DB - ((o * this.DB) % e);
    if (o-- > 0)
        for (a < this.DB && (r = this[o] >> a) > 0 && ((i = !0), (s = int2char(r))); o >= 0; )
            e > a ? ((r = (this[o] & ((1 << a) - 1)) << (e - a)), (r |= this[--o] >> (a += this.DB - e))) : ((r = (this[o] >> (a -= e)) & n), 0 >= a && ((a += this.DB), --o)), r > 0 && (i = !0), i && (s += int2char(r));
    return i ? s : "0";
}
function bnNegate() {
    var t = nbi();
    return BigInteger.ZERO.subTo(this, t), t;
}
function bnAbs() {
    return this.s < 0 ? this.negate() : this;
}
function bnCompareTo(t) {
    var e = this.s - t.s;
    if (0 != e) return e;
    var r = this.t;
    if (((e = r - t.t), 0 != e)) return this.s < 0 ? -e : e;
    for (; --r >= 0; ) if (0 != (e = this[r] - t[r])) return e;
    return 0;
}
function nbits(t) {
    var e,
        r = 1;
    return 0 != (e = t >>> 16) && ((t = e), (r += 16)), 0 != (e = t >> 8) && ((t = e), (r += 8)), 0 != (e = t >> 4) && ((t = e), (r += 4)), 0 != (e = t >> 2) && ((t = e), (r += 2)), 0 != (e = t >> 1) && ((t = e), (r += 1)), r;
}
function bnBitLength() {
    return this.t <= 0 ? 0 : this.DB * (this.t - 1) + nbits(this[this.t - 1] ^ (this.s & this.DM));
}
function bnpDLShiftTo(t, e) {
    var r;
    for (r = this.t - 1; r >= 0; --r) e[r + t] = this[r];
    for (r = t - 1; r >= 0; --r) e[r] = 0;
    (e.t = this.t + t), (e.s = this.s);
}
function bnpDRShiftTo(t, e) {
    for (var r = t; r < this.t; ++r) e[r - t] = this[r];
    (e.t = Math.max(this.t - t, 0)), (e.s = this.s);
}
function bnpLShiftTo(t, e) {
    var r,
        n = t % this.DB,
        i = this.DB - n,
        s = (1 << i) - 1,
        o = Math.floor(t / this.DB),
        a = (this.s << n) & this.DM;
    for (r = this.t - 1; r >= 0; --r) (e[r + o + 1] = (this[r] >> i) | a), (a = (this[r] & s) << n);
    for (r = o - 1; r >= 0; --r) e[r] = 0;
    (e[o] = a), (e.t = this.t + o + 1), (e.s = this.s), e.clamp();
}
function bnpRShiftTo(t, e) {
    e.s = this.s;
    var r = Math.floor(t / this.DB);
    if (r >= this.t) return void (e.t = 0);
    var n = t % this.DB,
        i = this.DB - n,
        s = (1 << n) - 1;
    e[0] = this[r] >> n;
    for (var o = r + 1; o < this.t; ++o) (e[o - r - 1] |= (this[o] & s) << i), (e[o - r] = this[o] >> n);
    n > 0 && (e[this.t - r - 1] |= (this.s & s) << i), (e.t = this.t - r), e.clamp();
}
function bnpSubTo(t, e) {
    for (var r = 0, n = 0, i = Math.min(t.t, this.t); i > r; ) (n += this[r] - t[r]), (e[r++] = n & this.DM), (n >>= this.DB);
    if (t.t < this.t) {
        for (n -= t.s; r < this.t; ) (n += this[r]), (e[r++] = n & this.DM), (n >>= this.DB);
        n += this.s;
    } else {
        for (n += this.s; r < t.t; ) (n -= t[r]), (e[r++] = n & this.DM), (n >>= this.DB);
        n -= t.s;
    }
    (e.s = 0 > n ? -1 : 0), -1 > n ? (e[r++] = this.DV + n) : n > 0 && (e[r++] = n), (e.t = r), e.clamp();
}
function bnpMultiplyTo(t, e) {
    var r = this.abs(),
        n = t.abs(),
        i = r.t;
    for (e.t = i + n.t; --i >= 0; ) e[i] = 0;
    for (i = 0; i < n.t; ++i) e[i + r.t] = r.am(0, n[i], e, i, 0, r.t);
    (e.s = 0), e.clamp(), this.s != t.s && BigInteger.ZERO.subTo(e, e);
}
function bnpSquareTo(t) {
    for (var e = this.abs(), r = (t.t = 2 * e.t); --r >= 0; ) t[r] = 0;
    for (r = 0; r < e.t - 1; ++r) {
        var n = e.am(r, e[r], t, 2 * r, 0, 1);
        (t[r + e.t] += e.am(r + 1, 2 * e[r], t, 2 * r + 1, n, e.t - r - 1)) >= e.DV && ((t[r + e.t] -= e.DV), (t[r + e.t + 1] = 1));
    }
    t.t > 0 && (t[t.t - 1] += e.am(r, e[r], t, 2 * r, 0, 1)), (t.s = 0), t.clamp();
}
function bnpDivRemTo(t, e, r) {
    var n = t.abs();
    if (!(n.t <= 0)) {
        var i = this.abs();
        if (i.t < n.t) return null != e && e.fromInt(0), void (null != r && this.copyTo(r));
        null == r && (r = nbi());
        var s = nbi(),
            o = this.s,
            a = t.s,
            h = this.DB - nbits(n[n.t - 1]);
        h > 0 ? (n.lShiftTo(h, s), i.lShiftTo(h, r)) : (n.copyTo(s), i.copyTo(r));
        var u = s.t,
            p = s[u - 1];
        if (0 != p) {
            var c = p * (1 << this.F1) + (u > 1 ? s[u - 2] >> this.F2 : 0),
                g = this.FV / c,
                l = (1 << this.F1) / c,
                f = 1 << this.F2,
                v = r.t,
                m = v - u,
                d = null == e ? nbi() : e;
            for (s.dlShiftTo(m, d), r.compareTo(d) >= 0 && ((r[r.t++] = 1), r.subTo(d, r)), BigInteger.ONE.dlShiftTo(u, d), d.subTo(s, s); s.t < u; ) s[s.t++] = 0;
            for (; --m >= 0; ) {
                var b = r[--v] == p ? this.DM : Math.floor(r[v] * g + (r[v - 1] + f) * l);
                if ((r[v] += s.am(0, b, r, m, 0, u)) < b) for (s.dlShiftTo(m, d), r.subTo(d, r); r[v] < --b; ) r.subTo(d, r);
            }
            null != e && (r.drShiftTo(u, e), o != a && BigInteger.ZERO.subTo(e, e)), (r.t = u), r.clamp(), h > 0 && r.rShiftTo(h, r), 0 > o && BigInteger.ZERO.subTo(r, r);
        }
    }
}
function bnMod(t) {
    var e = nbi();
    return this.abs().divRemTo(t, null, e), this.s < 0 && e.compareTo(BigInteger.ZERO) > 0 && t.subTo(e, e), e;
}
function Classic(t) {
    this.m = t;
}
function cConvert(t) {
    return t.s < 0 || t.compareTo(this.m) >= 0 ? t.mod(this.m) : t;
}
function cRevert(t) {
    return t;
}
function cReduce(t) {
    t.divRemTo(this.m, null, t);
}
function cMulTo(t, e, r) {
    t.multiplyTo(e, r), this.reduce(r);
}
function cSqrTo(t, e) {
    t.squareTo(e), this.reduce(e);
}
function bnpInvDigit() {
    if (this.t < 1) return 0;
    var t = this[0];
    if (0 == (1 & t)) return 0;
    var e = 3 & t;
    return (e = (e * (2 - (15 & t) * e)) & 15), (e = (e * (2 - (255 & t) * e)) & 255), (e = (e * (2 - (((65535 & t) * e) & 65535))) & 65535), (e = (e * (2 - ((t * e) % this.DV))) % this.DV), e > 0 ? this.DV - e : -e;
}
function Montgomery(t) {
    (this.m = t), (this.mp = t.invDigit()), (this.mpl = 32767 & this.mp), (this.mph = this.mp >> 15), (this.um = (1 << (t.DB - 15)) - 1), (this.mt2 = 2 * t.t);
}
function montConvert(t) {
    var e = nbi();
    return t.abs().dlShiftTo(this.m.t, e), e.divRemTo(this.m, null, e), t.s < 0 && e.compareTo(BigInteger.ZERO) > 0 && this.m.subTo(e, e), e;
}
function montRevert(t) {
    var e = nbi();
    return t.copyTo(e), this.reduce(e), e;
}
function montReduce(t) {
    for (; t.t <= this.mt2; ) t[t.t++] = 0;
    for (var e = 0; e < this.m.t; ++e) {
        var r = 32767 & t[e],
            n = (r * this.mpl + (((r * this.mph + (t[e] >> 15) * this.mpl) & this.um) << 15)) & t.DM;
        for (r = e + this.m.t, t[r] += this.m.am(0, n, t, e, 0, this.m.t); t[r] >= t.DV; ) (t[r] -= t.DV), t[++r]++;
    }
    t.clamp(), t.drShiftTo(this.m.t, t), t.compareTo(this.m) >= 0 && t.subTo(this.m, t);
}
function montSqrTo(t, e) {
    t.squareTo(e), this.reduce(e);
}
function montMulTo(t, e, r) {
    t.multiplyTo(e, r), this.reduce(r);
}
function bnpIsEven() {
    return 0 == (this.t > 0 ? 1 & this[0] : this.s);
}
function bnpExp(t, e) {
    if (t > 4294967295 || 1 > t) return BigInteger.ONE;
    var r = nbi(),
        n = nbi(),
        i = e.convert(this),
        s = nbits(t) - 1;
    for (i.copyTo(r); --s >= 0; )
        if ((e.sqrTo(r, n), (t & (1 << s)) > 0)) e.mulTo(n, i, r);
        else {
            var o = r;
            (r = n), (n = o);
        }
    return e.revert(r);
}
function bnModPowInt(t, e) {
    var r;
    return (r = 256 > t || e.isEven() ? new Classic(e) : new Montgomery(e)), this.exp(t, r);
}
function Arcfour() {
    (this.i = 0), (this.j = 0), (this.S = new Array());
}
function ARC4init(t) {
    var e, r, n;
    for (e = 0; 256 > e; ++e) this.S[e] = e;
    for (r = 0, e = 0; 256 > e; ++e) (r = (r + this.S[e] + t[e % t.length]) & 255), (n = this.S[e]), (this.S[e] = this.S[r]), (this.S[r] = n);
    (this.i = 0), (this.j = 0);
}
function ARC4next() {
    var t;
    return (this.i = (this.i + 1) & 255), (this.j = (this.j + this.S[this.i]) & 255), (t = this.S[this.i]), (this.S[this.i] = this.S[this.j]), (this.S[this.j] = t), this.S[(t + this.S[this.i]) & 255];
}
function prng_newstate() {
    return new Arcfour();
}
function rng_get_byte() {
    if (null == rng_state) {
        for (rng_state = prng_newstate(); rng_psize > rng_pptr; ) {
            var t = Math.floor(65536 * Math.random());
            rng_pool[rng_pptr++] = 255 & t;
        }
        for (rng_state.init(rng_pool), rng_pptr = 0; rng_pptr < rng_pool.length; ++rng_pptr) rng_pool[rng_pptr] = 0;
        rng_pptr = 0;
    }
    return rng_state.next();
}
function rng_get_bytes(t) {
    var e;
    for (e = 0; e < t.length; ++e) t[e] = rng_get_byte();
}
function SecureRandom() {}
function parseBigInt(t, e) {
    return new BigInteger(t, e);
}
function linebrk(t, e) {
    for (var r = "", n = 0; n + e < t.length; ) (r += t.substring(n, n + e) + "\n"), (n += e);
    return r + t.substring(n, t.length);
}
function byte2Hex(t) {
    return 16 > t ? "0" + t.toString(16) : t.toString(16);
}
function pkcs1pad2(t, e) {
    if (e < t.length + 11) return console.error("Message too long for RSA"), null;
    for (var r = new Array(), n = t.length - 1; n >= 0 && e > 0; ) {
        var i = t.charCodeAt(n--);
        128 > i ? (r[--e] = i) : i > 127 && 2048 > i ? ((r[--e] = (63 & i) | 128), (r[--e] = (i >> 6) | 192)) : ((r[--e] = (63 & i) | 128), (r[--e] = ((i >> 6) & 63) | 128), (r[--e] = (i >> 12) | 224));
    }
    r[--e] = 0;
    for (var s = new SecureRandom(), o = new Array(); e > 2; ) {
        for (o[0] = 0; 0 == o[0]; ) s.nextBytes(o);
        r[--e] = o[0];
    }
    return (r[--e] = 2), (r[--e] = 0), new BigInteger(r);
}
function RSAKey() {
    (this.n = null), (this.e = 0), (this.d = null), (this.p = null), (this.q = null), (this.dmp1 = null), (this.dmq1 = null), (this.coeff = null);
}
function RSASetPublic(t, e) {
    null != t && null != e && t.length > 0 && e.length > 0 ? ((this.n = parseBigInt(t, 16)), (this.e = parseInt(e, 16))) : console.error("Invalid RSA public key");
}
function RSADoPublic(t) {
    return t.modPowInt(this.e, this.n);
}
function RSAEncrypt(t) {
    var e = pkcs1pad2(t, (this.n.bitLength() + 7) >> 3);
    if (null == e) return null;
    var r = this.doPublic(e);
    if (null == r) return null;
    var n = r.toString(16);
    return 0 == (1 & n.length) ? n : "0" + n;
}
function hex2b64(t) {
    var e,
        r,
        n = "";
    for (e = 0; e + 3 <= t.length; e += 3) (r = parseInt(t.substring(e, e + 3), 16)), (n += b64map.charAt(r >> 6) + b64map.charAt(63 & r));
    for (
        e + 1 == t.length ? ((r = parseInt(t.substring(e, e + 1), 16)), (n += b64map.charAt(r << 2))) : e + 2 == t.length && ((r = parseInt(t.substring(e, e + 2), 16)), (n += b64map.charAt(r >> 2) + b64map.charAt((3 & r) << 4)));
        (3 & n.length) > 0;

    )
        n += b64pad;
    return n;
}
function b64tohex(t) {
    var e,
        r,
        n = "",
        i = 0;
    for (e = 0; e < t.length && t.charAt(e) != b64pad; ++e)
        (v = b64map.indexOf(t.charAt(e))),
            v < 0 ||
                (0 == i
                    ? ((n += int2char(v >> 2)), (r = 3 & v), (i = 1))
                    : 1 == i
                    ? ((n += int2char((r << 2) | (v >> 4))), (r = 15 & v), (i = 2))
                    : 2 == i
                    ? ((n += int2char(r)), (n += int2char(v >> 2)), (r = 3 & v), (i = 3))
                    : ((n += int2char((r << 2) | (v >> 4))), (n += int2char(15 & v)), (i = 0)));
    return 1 == i && (n += int2char(r << 2)), n;
}
function b64toBA(t) {
    var e,
        r = b64tohex(t),
        n = new Array();
    for (e = 0; 2 * e < r.length; ++e) n[e] = parseInt(r.substring(2 * e, 2 * e + 2), 16);
    return n;
}
function createInput(t, e, r) {
    var n = document.createElement("input");
    n.setAttribute("type", "hidden"), n.setAttribute("name", e), n.setAttribute("value", r), t.append(n);
}
function checkInputName(t) {
    t.getAttribute("name") && t.removeAttribute("name");
}
var dbits,
    canary = 0xdeadbeefcafe,
    j_lm = 15715070 == (16777215 & canary);
j_lm && "Microsoft Internet Explorer" == navigator.appName
    ? ((BigInteger.prototype.am = am2), (dbits = 30))
    : j_lm && "Netscape" != navigator.appName
    ? ((BigInteger.prototype.am = am1), (dbits = 26))
    : ((BigInteger.prototype.am = am3), (dbits = 28)),
    (BigInteger.prototype.DB = dbits),
    (BigInteger.prototype.DM = (1 << dbits) - 1),
    (BigInteger.prototype.DV = 1 << dbits);
var BI_FP = 52;
(BigInteger.prototype.FV = Math.pow(2, BI_FP)), (BigInteger.prototype.F1 = BI_FP - dbits), (BigInteger.prototype.F2 = 2 * dbits - BI_FP);
var BI_RM = "0123456789abcdefghijklmnopqrstuvwxyz",
    BI_RC = new Array(),
    rr,
    vv;
for (rr = "0".charCodeAt(0), vv = 0; 9 >= vv; ++vv) BI_RC[rr++] = vv;
for (rr = "a".charCodeAt(0), vv = 10; 36 > vv; ++vv) BI_RC[rr++] = vv;
for (rr = "A".charCodeAt(0), vv = 10; 36 > vv; ++vv) BI_RC[rr++] = vv;
(Classic.prototype.convert = cConvert),
    (Classic.prototype.revert = cRevert),
    (Classic.prototype.reduce = cReduce),
    (Classic.prototype.mulTo = cMulTo),
    (Classic.prototype.sqrTo = cSqrTo),
    (Montgomery.prototype.convert = montConvert),
    (Montgomery.prototype.revert = montRevert),
    (Montgomery.prototype.reduce = montReduce),
    (Montgomery.prototype.mulTo = montMulTo),
    (Montgomery.prototype.sqrTo = montSqrTo),
    (BigInteger.prototype.copyTo = bnpCopyTo),
    (BigInteger.prototype.fromInt = bnpFromInt),
    (BigInteger.prototype.fromString = bnpFromString),
    (BigInteger.prototype.clamp = bnpClamp),
    (BigInteger.prototype.dlShiftTo = bnpDLShiftTo),
    (BigInteger.prototype.drShiftTo = bnpDRShiftTo),
    (BigInteger.prototype.lShiftTo = bnpLShiftTo),
    (BigInteger.prototype.rShiftTo = bnpRShiftTo),
    (BigInteger.prototype.subTo = bnpSubTo),
    (BigInteger.prototype.multiplyTo = bnpMultiplyTo),
    (BigInteger.prototype.squareTo = bnpSquareTo),
    (BigInteger.prototype.divRemTo = bnpDivRemTo),
    (BigInteger.prototype.invDigit = bnpInvDigit),
    (BigInteger.prototype.isEven = bnpIsEven),
    (BigInteger.prototype.exp = bnpExp),
    (BigInteger.prototype.toString = bnToString),
    (BigInteger.prototype.negate = bnNegate),
    (BigInteger.prototype.abs = bnAbs),
    (BigInteger.prototype.compareTo = bnCompareTo),
    (BigInteger.prototype.bitLength = bnBitLength),
    (BigInteger.prototype.mod = bnMod),
    (BigInteger.prototype.modPowInt = bnModPowInt),
    (BigInteger.ZERO = nbv(0)),
    (BigInteger.ONE = nbv(1)),
    (Arcfour.prototype.init = ARC4init),
    (Arcfour.prototype.next = ARC4next);
var rng_psize = 256,
    rng_state,
    rng_pool,
    rng_pptr;
if (null == rng_pool) {
    (rng_pool = new Array()), (rng_pptr = 0);
    var t;
    if (window.crypto && window.crypto.getRandomValues) {
        var z = new Uint32Array(256);
        for (window.crypto.getRandomValues(z), t = 0; t < z.length; ++t) rng_pool[rng_pptr++] = 255 & z[t];
    }
    var onMouseMoveListener = function (t) {
        if (((this.count = this.count || 0), this.count >= 256 || rng_pptr >= rng_psize))
            return void (window.removeEventListener ? window.removeEventListener("mousemove", onMouseMoveListener, !1) : window.detachEvent && window.detachEvent("onmousemove", onMouseMoveListener));
        try {
            var e = t.x + t.y;
            (rng_pool[rng_pptr++] = 255 & e), (this.count += 1);
        } catch (r) {}
    };
    window.addEventListener ? window.addEventListener("mousemove", onMouseMoveListener, !1) : window.attachEvent && window.attachEvent("onmousemove", onMouseMoveListener);
}
(SecureRandom.prototype.nextBytes = rng_get_bytes), (RSAKey.prototype.doPublic = RSADoPublic), (RSAKey.prototype.setPublic = RSASetPublic), (RSAKey.prototype.encrypt = RSAEncrypt);
var b64map = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
    b64pad = "=";
!(function (t) {
    "use strict";
    function e(t, r) {
        t instanceof e ? ((this.enc = t.enc), (this.pos = t.pos)) : ((this.enc = t), (this.pos = r));
    }
    function r(t, e, r, n, i) {
        (this.stream = t), (this.header = e), (this.length = r), (this.tag = n), (this.sub = i);
    }
    var n = 100,
        i = "â€¦",
        s = {
            tag: function (t, e) {
                var r = document.createElement(t);
                return (r.className = e), r;
            },
            text: function (t) {
                return document.createTextNode(t);
            },
        };
    (e.prototype.get = function (e) {
        if ((e === t && (e = this.pos++), e >= this.enc.length)) throw "Requesting byte offset " + e + " on a stream of length " + this.enc.length;
        return this.enc[e];
    }),
        (e.prototype.hexDigits = "0123456789ABCDEF"),
        (e.prototype.hexByte = function (t) {
            return this.hexDigits.charAt((t >> 4) & 15) + this.hexDigits.charAt(15 & t);
        }),
        (e.prototype.hexDump = function (t, e, r) {
            for (var n = "", i = t; e > i; ++i)
                if (((n += this.hexByte(this.get(i))), r !== !0))
                    switch (15 & i) {
                        case 7:
                            n += "  ";
                            break;
                        case 15:
                            n += "\n";
                            break;
                        default:
                            n += " ";
                    }
            return n;
        }),
        (e.prototype.parseStringISO = function (t, e) {
            for (var r = "", n = t; e > n; ++n) r += String.fromCharCode(this.get(n));
            return r;
        }),
        (e.prototype.parseStringUTF = function (t, e) {
            for (var r = "", n = t; e > n; ) {
                var i = this.get(n++);
                r += 128 > i ? String.fromCharCode(i) : i > 191 && 224 > i ? String.fromCharCode(((31 & i) << 6) | (63 & this.get(n++))) : String.fromCharCode(((15 & i) << 12) | ((63 & this.get(n++)) << 6) | (63 & this.get(n++)));
            }
            return r;
        }),
        (e.prototype.parseStringBMP = function (t, e) {
            for (var r = "", n = t; e > n; n += 2) {
                var i = this.get(n),
                    s = this.get(n + 1);
                r += String.fromCharCode((i << 8) + s);
            }
            return r;
        }),
        (e.prototype.reTime = /^((?:1[89]|2\d)?\d\d)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])([01]\d|2[0-3])(?:([0-5]\d)(?:([0-5]\d)(?:[.,](\d{1,3}))?)?)?(Z|[-+](?:[0]\d|1[0-2])([0-5]\d)?)?$/),
        (e.prototype.parseTime = function (t, e) {
            var r = this.parseStringISO(t, e),
                n = this.reTime.exec(r);
            return n
                ? ((r = n[1] + "-" + n[2] + "-" + n[3] + " " + n[4]), n[5] && ((r += ":" + n[5]), n[6] && ((r += ":" + n[6]), n[7] && (r += "." + n[7]))), n[8] && ((r += " UTC"), "Z" != n[8] && ((r += n[8]), n[9] && (r += ":" + n[9]))), r)
                : "Unrecognized time: " + r;
        }),
        (e.prototype.parseInteger = function (t, e) {
            var r = e - t;
            if (r > 4) {
                r <<= 3;
                var n = this.get(t);
                if (0 === n) r -= 8;
                else for (; 128 > n; ) (n <<= 1), --r;
                return "(" + r + " bit)";
            }
            for (var i = 0, s = t; e > s; ++s) i = (i << 8) | this.get(s);
            return i;
        }),
        (e.prototype.parseBitString = function (t, e) {
            var r = this.get(t),
                n = ((e - t - 1) << 3) - r,
                i = "(" + n + " bit)";
            if (20 >= n) {
                var s = r;
                i += " ";
                for (var o = e - 1; o > t; --o) {
                    for (var a = this.get(o), h = s; 8 > h; ++h) i += (a >> h) & 1 ? "1" : "0";
                    s = 0;
                }
            }
            return i;
        }),
        (e.prototype.parseOctetString = function (t, e) {
            var r = e - t,
                s = "(" + r + " byte) ";
            r > n && (e = t + n);
            for (var o = t; e > o; ++o) s += this.hexByte(this.get(o));
            return r > n && (s += i), s;
        }),
        (e.prototype.parseOID = function (t, e) {
            for (var r = "", n = 0, i = 0, s = t; e > s; ++s) {
                var o = this.get(s);
                if (((n = (n << 7) | (127 & o)), (i += 7), !(128 & o))) {
                    if ("" === r) {
                        var a = 80 > n ? (40 > n ? 0 : 1) : 2;
                        r = a + "." + (n - 40 * a);
                    } else r += "." + (i >= 31 ? "bigint" : n);
                    n = i = 0;
                }
            }
            return r;
        }),
        (r.prototype.typeName = function () {
            if (this.tag === t) return "unknown";
            var e = this.tag >> 6,
                r = ((this.tag >> 5) & 1, 31 & this.tag);
            switch (e) {
                case 0:
                    switch (r) {
                        case 0:
                            return "EOC";
                        case 1:
                            return "BOOLEAN";
                        case 2:
                            return "INTEGER";
                        case 3:
                            return "BIT_STRING";
                        case 4:
                            return "OCTET_STRING";
                        case 5:
                            return "NULL";
                        case 6:
                            return "OBJECT_IDENTIFIER";
                        case 7:
                            return "ObjectDescriptor";
                        case 8:
                            return "EXTERNAL";
                        case 9:
                            return "REAL";
                        case 10:
                            return "ENUMERATED";
                        case 11:
                            return "EMBEDDED_PDV";
                        case 12:
                            return "UTF8String";
                        case 16:
                            return "SEQUENCE";
                        case 17:
                            return "SET";
                        case 18:
                            return "NumericString";
                        case 19:
                            return "PrintableString";
                        case 20:
                            return "TeletexString";
                        case 21:
                            return "VideotexString";
                        case 22:
                            return "IA5String";
                        case 23:
                            return "UTCTime";
                        case 24:
                            return "GeneralizedTime";
                        case 25:
                            return "GraphicString";
                        case 26:
                            return "VisibleString";
                        case 27:
                            return "GeneralString";
                        case 28:
                            return "UniversalString";
                        case 30:
                            return "BMPString";
                        default:
                            return "Universal_" + r.toString(16);
                    }
                case 1:
                    return "Application_" + r.toString(16);
                case 2:
                    return "[" + r + "]";
                case 3:
                    return "Private_" + r.toString(16);
            }
        }),
        (r.prototype.reSeemsASCII = /^[ -~]+$/),
        (r.prototype.content = function () {
            if (this.tag === t) return null;
            var e = this.tag >> 6,
                r = 31 & this.tag,
                s = this.posContent(),
                o = Math.abs(this.length);
            if (0 !== e) {
                if (null !== this.sub) return "(" + this.sub.length + " elem)";
                var a = this.stream.parseStringISO(s, s + Math.min(o, n));
                return this.reSeemsASCII.test(a) ? a.substring(0, 2 * n) + (a.length > 2 * n ? i : "") : this.stream.parseOctetString(s, s + o);
            }
            switch (r) {
                case 1:
                    return 0 === this.stream.get(s) ? "false" : "true";
                case 2:
                    return this.stream.parseInteger(s, s + o);
                case 3:
                    return this.sub ? "(" + this.sub.length + " elem)" : this.stream.parseBitString(s, s + o);
                case 4:
                    return this.sub ? "(" + this.sub.length + " elem)" : this.stream.parseOctetString(s, s + o);
                case 6:
                    return this.stream.parseOID(s, s + o);
                case 16:
                case 17:
                    return "(" + this.sub.length + " elem)";
                case 12:
                    return this.stream.parseStringUTF(s, s + o);
                case 18:
                case 19:
                case 20:
                case 21:
                case 22:
                case 26:
                    return this.stream.parseStringISO(s, s + o);
                case 30:
                    return this.stream.parseStringBMP(s, s + o);
                case 23:
                case 24:
                    return this.stream.parseTime(s, s + o);
            }
            return null;
        }),
        (r.prototype.toString = function () {
            return this.typeName() + "@" + this.stream.pos + "[header:" + this.header + ",length:" + this.length + ",sub:" + (null === this.sub ? "null" : this.sub.length) + "]";
        }),
        (r.prototype.print = function (e) {
            if ((e === t && (e = ""), document.writeln(e + this), null !== this.sub)) {
                e += "  ";
                for (var r = 0, n = this.sub.length; n > r; ++r) this.sub[r].print(e);
            }
        }),
        (r.prototype.toPrettyString = function (e) {
            e === t && (e = "");
            var r = e + this.typeName() + " @" + this.stream.pos;
            if ((this.length >= 0 && (r += "+"), (r += this.length), 32 & this.tag ? (r += " (constructed)") : (3 != this.tag && 4 != this.tag) || null === this.sub || (r += " (encapsulates)"), (r += "\n"), null !== this.sub)) {
                e += "  ";
                for (var n = 0, i = this.sub.length; i > n; ++n) r += this.sub[n].toPrettyString(e);
            }
            return r;
        }),
        (r.prototype.toDOM = function () {
            var t = s.tag("div", "node");
            t.asn1 = this;
            var e = s.tag("div", "head"),
                r = this.typeName().replace(/_/g, " ");
            e.innerHTML = r;
            var n = this.content();
            if (null !== n) {
                n = String(n).replace(/</g, "&lt;");
                var i = s.tag("span", "preview");
                i.appendChild(s.text(n)), e.appendChild(i);
            }
            t.appendChild(e), (this.node = t), (this.head = e);
            var o = s.tag("div", "value");
            if (
                ((r = "Offset: " + this.stream.pos + "<br/>"),
                (r += "Length: " + this.header + "+"),
                (r += this.length >= 0 ? this.length : -this.length + " (undefined)"),
                32 & this.tag ? (r += "<br/>(constructed)") : (3 != this.tag && 4 != this.tag) || null === this.sub || (r += "<br/>(encapsulates)"),
                null !== n && ((r += "<br/>Value:<br/><b>" + n + "</b>"), "object" == typeof oids && 6 == this.tag))
            ) {
                var a = oids[n];
                a && (a.d && (r += "<br/>" + a.d), a.c && (r += "<br/>" + a.c), a.w && (r += "<br/>(warning!)"));
            }
            (o.innerHTML = r), t.appendChild(o);
            var h = s.tag("div", "sub");
            if (null !== this.sub) for (var u = 0, p = this.sub.length; p > u; ++u) h.appendChild(this.sub[u].toDOM());
            return (
                t.appendChild(h),
                (e.onclick = function () {
                    t.className = "node collapsed" == t.className ? "node" : "node collapsed";
                }),
                t
            );
        }),
        (r.prototype.posStart = function () {
            return this.stream.pos;
        }),
        (r.prototype.posContent = function () {
            return this.stream.pos + this.header;
        }),
        (r.prototype.posEnd = function () {
            return this.stream.pos + this.header + Math.abs(this.length);
        }),
        (r.prototype.fakeHover = function (t) {
            (this.node.className += " hover"), t && (this.head.className += " hover");
        }),
        (r.prototype.fakeOut = function (t) {
            var e = / ?hover/;
            (this.node.className = this.node.className.replace(e, "")), t && (this.head.className = this.head.className.replace(e, ""));
        }),
        (r.prototype.toHexDOM_sub = function (t, e, r, n, i) {
            if (!(n >= i)) {
                var o = s.tag("span", e);
                o.appendChild(s.text(r.hexDump(n, i))), t.appendChild(o);
            }
        }),
        (r.prototype.toHexDOM = function (e) {
            var r = s.tag("span", "hex");
            if (
                (e === t && (e = r),
                (this.head.hexNode = r),
                (this.head.onmouseover = function () {
                    this.hexNode.className = "hexCurrent";
                }),
                (this.head.onmouseout = function () {
                    this.hexNode.className = "hex";
                }),
                (r.asn1 = this),
                (r.onmouseover = function () {
                    var t = !e.selected;
                    t && ((e.selected = this.asn1), (this.className = "hexCurrent")), this.asn1.fakeHover(t);
                }),
                (r.onmouseout = function () {
                    var t = e.selected == this.asn1;
                    this.asn1.fakeOut(t), t && ((e.selected = null), (this.className = "hex"));
                }),
                this.toHexDOM_sub(r, "tag", this.stream, this.posStart(), this.posStart() + 1),
                this.toHexDOM_sub(r, this.length >= 0 ? "dlen" : "ulen", this.stream, this.posStart() + 1, this.posContent()),
                null === this.sub)
            )
                r.appendChild(s.text(this.stream.hexDump(this.posContent(), this.posEnd())));
            else if (this.sub.length > 0) {
                var n = this.sub[0],
                    i = this.sub[this.sub.length - 1];
                this.toHexDOM_sub(r, "intro", this.stream, this.posContent(), n.posStart());
                for (var o = 0, a = this.sub.length; a > o; ++o) r.appendChild(this.sub[o].toHexDOM(e));
                this.toHexDOM_sub(r, "outro", this.stream, i.posEnd(), this.posEnd());
            }
            return r;
        }),
        (r.prototype.toHexString = function (t) {
            return this.stream.hexDump(this.posStart(), this.posEnd(), !0);
        }),
        (r.decodeLength = function (t) {
            var e = t.get(),
                r = 127 & e;
            if (r == e) return r;
            if (r > 3) throw "Length over 24 bits not supported at position " + (t.pos - 1);
            if (0 === r) return -1;
            e = 0;
            for (var n = 0; r > n; ++n) e = (e << 8) | t.get();
            return e;
        }),
        (r.hasContent = function (t, n, i) {
            if (32 & t) return !0;
            if (3 > t || t > 4) return !1;
            var s = new e(i);
            3 == t && s.get();
            var o = s.get();
            if ((o >> 6) & 1) return !1;
            try {
                var a = r.decodeLength(s);
                return s.pos - i.pos + a == n;
            } catch (h) {
                return !1;
            }
        }),
        (r.decode = function (t) {
            t instanceof e || (t = new e(t, 0));
            var n = new e(t),
                i = t.get(),
                s = r.decodeLength(t),
                o = t.pos - n.pos,
                a = null;
            if (r.hasContent(i, s, t)) {
                var h = t.pos;
                if ((3 == i && t.get(), (a = []), s >= 0)) {
                    for (var u = h + s; t.pos < u; ) a[a.length] = r.decode(t);
                    if (t.pos != u) throw "Content size is not correct for container starting at offset " + h;
                } else
                    try {
                        for (;;) {
                            var p = r.decode(t);
                            if (0 === p.tag) break;
                            a[a.length] = p;
                        }
                        s = h - t.pos;
                    } catch (c) {
                        throw "Exception while decoding undefined length content: " + c;
                    }
            } else t.pos += s;
            return new r(n, o, s, i, a);
        }),
        (r.test = function () {
            for (
                var t = [
                        { value: [39], expected: 39 },
                        { value: [129, 201], expected: 201 },
                        { value: [131, 254, 220, 186], expected: 16702650 },
                    ],
                    n = 0,
                    i = t.length;
                i > n;
                ++n
            ) {
                var s = new e(t[n].value, 0),
                    o = r.decodeLength(s);
                o != t[n].expected && document.write("In test[" + n + "] expected " + t[n].expected + " got " + o + "\n");
            }
        }),
        (window.ASN1 = r);
})();
var Base64 = (function (t) {
    "use strict";
    var e,
        r = /-----BEGIN [^-]+-----([A-Za-z0-9+\/=\s]+)-----END [^-]+-----|begin-base64[^\n]+\n([A-Za-z0-9+\/=\s]+)====/,
        n = function (t) {
            var r;
            if (void 0 === e) {
                var n = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
                    i = "= \f\n\r	Â \u2028\u2029";
                for (e = [], r = 0; 64 > r; ++r) e[n.charAt(r)] = r;
                for (r = 0; r < i.length; ++r) e[i.charAt(r)] = -1;
            }
            var s = [],
                o = 0,
                a = 0;
            for (r = 0; r < t.length; ++r) {
                var h = t.charAt(r);
                if ("=" === h) break;
                if (((h = e[h]), -1 !== h)) {
                    if (void 0 === h) throw "Illegal character at offset " + r;
                    (o |= h), ++a >= 4 ? ((s[s.length] = o >> 16), (s[s.length] = (o >> 8) & 255), (s[s.length] = 255 & o), (o = 0), (a = 0)) : (o <<= 6);
                }
            }
            switch (a) {
                case 1:
                    throw "Base64 encoding incomplete: at least 2 bits missing";
                case 2:
                    s[s.length] = o >> 10;
                    break;
                case 3:
                    (s[s.length] = o >> 16), (s[s.length] = (o >> 8) & 255);
            }
            return s;
        };
    return (
        (t.unarmorCSE = function (t) {
            var e = r.exec(t);
            if (e)
                if (e[1]) t = e[1];
                else {
                    if (!e[2]) throw "RegExp out of sync";
                    t = e[2];
                }
            return n(t);
        }),
        t
    );
})(Base64 || {});
!(function (t) {
    "use strict";
    var e,
        r = {};
    (r.decode = function (r) {
        var n;
        if (e === t) {
            var i = "0123456789ABCDEF",
                s = " \f\n\r	Â \u2028\u2029";
            for (e = [], n = 0; 16 > n; ++n) e[i.charAt(n)] = n;
            for (i = i.toLowerCase(), n = 10; 16 > n; ++n) e[i.charAt(n)] = n;
            for (n = 0; n < s.length; ++n) e[s.charAt(n)] = -1;
        }
        var o = [],
            a = 0,
            h = 0;
        for (n = 0; n < r.length; ++n) {
            var u = r.charAt(n);
            if ("=" == u) break;
            if (((u = e[u]), -1 != u)) {
                if (u === t) throw "Illegal character at offset " + n;
                (a |= u), ++h >= 2 ? ((o[o.length] = a), (a = 0), (h = 0)) : (a <<= 4);
            }
        }
        if (h) throw "Hex encoding incomplete: 4 bits missing";
        return o;
    }),
        (window.Hex = r);
})(),
    (ASN1.prototype.getHexStringValue = function () {
        var t = this.toHexString(),
            e = 2 * this.header,
            r = 2 * this.length;
        return t.substr(e, r);
    }),
    (RSAKey.prototype.parseKey = function (t) {
        var e, r, n, i, s, o, a;
        try {
            return (
                (e = 0),
                (r = 0),
                (n = /^\s*(?:[0-9A-Fa-f][0-9A-Fa-f]\s*)+$/),
                (i = n.test(t) ? Hex.decode(t) : Base64.unarmorCSE(t)),
                (s = ASN1.decode(i)),
                2 !== s.sub.length ? !1 : ((o = s.sub[1]), (a = o.sub[0]), (e = a.sub[0].getHexStringValue()), (this.n = parseBigInt(e, 16)), (r = a.sub[1].getHexStringValue()), (this.e = parseInt(r, 16)), !0)
            );
        } catch (h) {
            return !1;
        }
    });
var BeGatewayCSE = function (t) {
    (this.queryDataSelector = t.queryDataSelector || "data-encrypted-name"), (this.key = t.publicKey ? t.publicKey : t), (this.version = t.version || "1_0_0"), RSAKey.call(this), RSAKey.prototype.parseKey(this.key);
};

/*
BeGatewayCSE.prototype.encrypt = function (t) {
    var e, r, n, i, s;
    if (!t) return null;
    if (((e = document.getElementById(t)), (r = e.querySelectorAll("[" + this.queryDataSelector + "]")), !r || !r.length)) return null;
    for (var o = r.length - 1; o >= 0; o--)
        (n = r[o].getAttribute(this.queryDataSelector)),
            (i = r[o].value),
            "encrypted_number" === n && createInput(e, "credit_card_last_4", i.toString().slice(-4)),
            (s = hex2b64(RSAKey.prototype.encrypt(i))),
            (i = "$begatewaycsejs_" + this.version + "$" + s),
            createInput(e, n, i),
            checkInputName(r[o]);
};
*/

BeGatewayCSE.prototype.encrypt = function (data) {
    return "$begatewaycsejs_" + this.version + "$" + hex2b64(RSAKey.prototype.encrypt(data));
};
