const candidatos = {
    "u1": "https://images.unsplash.com/photo-1515562141207-7a88fb765336?w=900&q=80&auto=format&fit=crop",
    "u2": "https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=900&q=80&auto=format&fit=crop",
    "u3": "https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=900&q=80&auto=format&fit=crop",
    "u4": "https://images.unsplash.com/photo-1573408301185-9148baa40f9c?w=900&q=80&auto=format&fit=crop",
    "u5": "https://images.unsplash.com/photo-1512163143273-bde0e3cc7407?w=900&q=80&auto=format&fit=crop",
    "u6": "https://images.unsplash.com/photo-1584302179602-e4c3d3fd641d?w=900&q=80&auto=format&fit=crop",
    "u7": "https://images.unsplash.com/photo-1611591437281-460bfbe122a5?w=900&q=80&auto=format&fit=crop",
    "u8": "https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=900&q=80&auto=format&fit=crop",
    "u9": "https://images.unsplash.com/photo-1602173574767-37ac01994b2a?w=900&q=80&auto=format&fit=crop",
    "u10": "https://images.unsplash.com/photo-1596944924616-7b38e7cfac36?w=900&q=80&auto=format&fit=crop",
    "u11": "https://images.unsplash.com/photo-1620656798579-1984d9e77df2?w=900&q=80&auto=format&fit=crop",
    "u12": "https://images.unsplash.com/photo-1610634850326-c346b2a1c4d6?w=900&q=80&auto=format&fit=crop",
    "u13": "https://images.unsplash.com/photo-1633934542430-9b7a4f7c7e2c?w=900&q=80&auto=format&fit=crop",
    "u14": "https://images.unsplash.com/photo-1608041831122-6e5480a0e1e0?w=900&q=80&auto=format&fit=crop",
    "u15": "https://images.unsplash.com/photo-1617038220319-276d3cfcb6df?w=900&q=80&auto=format&fit=crop",
    "u16": "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&q=80&auto=format&fit=crop",
    "p1": "https://images.pexels.com/photos/1452765/pexels-photo-1452765.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p2": "https://images.pexels.com/photos/7245565/pexels-photo-7245565.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p3": "https://images.pexels.com/photos/1191719/pexels-photo-1191719.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p4": "https://images.pexels.com/photos/279906/pexels-photo-279906.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p5": "https://images.pexels.com/photos/2669957/pexels-photo-2669957.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p6": "https://images.pexels.com/photos/1786632/pexels-photo-1786632.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p7": "https://images.pexels.com/photos/1927259/pexels-photo-1927259.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p8": "https://images.pexels.com/photos/4210866/pexels-photo-4210866.jpeg?auto=compress&cs=tinysrgb&w=900",
};

const buenos = {};
for (const [k, url] of Object.entries(candidatos)) {
    try {
        const r = await fetch(url, { method: "GET", headers: { "User-Agent": "Mozilla/5.0" } });
        const ct = r.headers.get("content-type") || "";
        const cl = r.headers.get("content-length") || "?";
        if (r.ok && ct.startsWith("image")) {
            buenos[k] = url;
            console.log(`OK  ${k} (${ct}, ${cl}b)`);
        } else {
            console.log(`ERR ${k} status=${r.status} ct=${ct}`);
        }
    } catch (e) {
        console.log(`ERR ${k} ${String(e).slice(0, 60)}`);
    }
}
console.log("\ntotal validas:", Object.keys(buenos).length);
console.log(JSON.stringify(Object.keys(buenos)));
