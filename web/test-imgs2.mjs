const candidatos = {
    "u17": "https://images.unsplash.com/photo-1589674781896-e7f13f533ed3?w=900&q=80&auto=format&fit=crop",
    "u18": "https://images.unsplash.com/photo-1567721913486-6585f069b332?w=900&q=80&auto=format&fit=crop",
    "u19": "https://images.unsplash.com/photo-1587145820266-a5951ee6f620?w=900&q=80&auto=format&fit=crop",
    "u20": "https://images.unsplash.com/photo-1611085583761-1ee9e9115f13?w=900&q=80&auto=format&fit=crop",
    "u21": "https://images.unsplash.com/photo-1524805444758-089144d191c9?w=900&q=80&auto=format&fit=crop",
    "u22": "https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=900&q=80&auto=format&fit=crop",
    "u23": "https://images.unsplash.com/photo-1547996160-81dfa63595aa?w=900&q=80&auto=format&fit=crop",
    "u24": "https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?w=900&q=80&auto=format&fit=crop",
    "u25": "https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=900&q=80&auto=format&fit=crop",
    "u26": "https://images.unsplash.com/photo-1509048191080-d2984bad6ae5?w=900&q=80&auto=format&fit=crop",
    "u27": "https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=900&q=80&auto=format&fit=crop",
    "u28": "https://images.unsplash.com/photo-1611955167811-6767c209288f?w=900&q=80&auto=format&fit=crop",
    "u29": "https://images.unsplash.com/photo-1584231277589-581e96b4a317?w=900&q=80&auto=format&fit=crop",
    "u30": "https://images.unsplash.com/photo-1519744792095-0f4469dcef76?w=900&q=80&auto=format&fit=crop",
    "u31": "https://images.unsplash.com/photo-1596462508660-95cbd7a5d665?w=900&q=80&auto=format&fit=crop",
    "u32": "https://images.unsplash.com/photo-1543512212-761c67f2f765?w=900&q=80&auto=format&fit=crop",
    "u33": "https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?w=900&q=80&auto=format&fit=crop",
    "u34": "https://images.unsplash.com/photo-1600721391689-2564bb8055de?w=900&q=80&auto=format&fit=crop",
    "u35": "https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=900&q=80&auto=format&fit=crop",
    "u36": "https://images.unsplash.com/photo-1615655400236-ea61759ea1a8?w=900&q=80&auto=format&fit=crop",
    "p9": "https://images.pexels.com/photos/1083822/pexels-photo-1083822.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p10": "https://images.pexels.com/photos/1889351/pexels-photo-1889351.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p11": "https://images.pexels.com/photos/2412658/pexels-photo-2412658.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p12": "https://images.pexels.com/photos/3687976/pexels-photo-3687976.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p13": "https://images.pexels.com/photos/1029896/pexels-photo-1029896.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p14": "https://images.pexels.com/photos/259580/pexels-photo-259580.jpeg?auto=compress&cs=tinysrgb&w=900",
    "p15": "https://images.pexels.com/photos/2987219/pexels-photo-2987219.jpeg?auto=compress&cs=tinysrgb&w=900",
};

for (const [k, url] of Object.entries(candidatos)) {
    try {
        const r = await fetch(url, { headers: { "User-Agent": "Mozilla/5.0" } });
        const ct = r.headers.get("content-type") || "";
        if (r.ok && ct.startsWith("image")) {
            console.log(`OK  ${k}`);
        } else {
            console.log(`ERR ${k} status=${r.status}`);
        }
    } catch (e) {
        console.log(`ERR ${k} ${String(e).slice(0, 50)}`);
    }
}
