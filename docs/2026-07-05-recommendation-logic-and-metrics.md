# Recommendation Logic & Success Metrics

> เติม Open Items จาก [design spec](2026-07-05-grocery-list-webapp-design.md)
> วันที่: 2026-07-05 · ขอบเขต: MVP · แนวทาง: **rule-based (ไม่ใช้ ML)**

---

## Part 1 — Recommendation Logic (MVP)

MVP ใช้ **rule-based deterministic** ทั้งหมด — อธิบายได้ทุกการแนะนำ (รองรับ UX move #4 "ทำไมแนะนำ") และไม่ต้องมี data เยอะแบบ ML

### 1.1 Input จาก Wizard (spec ของผู้ใช้)
| field | ค่า | ใช้ทำอะไร |
|---|---|---|
| `budget` | ตัวเลข (฿) | เพดานการจัดของ |
| `room_type` | studio / 1-bedroom / shared | กรองของที่เกี่ยวข้อง |
| `occupants` | 1 / 2 / 3+ | scale ปริมาณของสิ้นเปลือง |
| `cooking` | never / sometimes / often | เปิด/ปิดหมวดครัว |
| `owned_items` | checklist | ตัดของที่มีอยู่แล้วออก |

### 1.2 Data model ของสินค้า (แต่ละชิ้นต้องมี)
| field | ตัวอย่าง | หมายเหตุ |
|---|---|---|
| `category` | ครัว / นอน / น้ำ / ทำความสะอาด / ของกินตุน | จัดกลุ่มตาม mental model "ห้อง" |
| `tier` | **must** / recommended / optional | หัวใจของการตัดตอนเกินงบ |
| `mode` | move-in (ถาวร) / restock (สิ้นเปลือง) | 2 โหมด |
| `triggers` | เช่น `cooking >= sometimes` | เงื่อนไข spec ที่ทำให้สินค้านี้ถูกแนะนำ |
| `pairs_with` | [product_id, …] | สร้าง Smart Bundle (cascading) |
| `qty_scales_by` | เช่น `occupants` | ปริมาณเพิ่มตามจำนวนคน |
| `restock_cadence` | weekly / monthly | ใช้ในแท็บปฏิทิน |
| `ref_price` + `platform_prices[]` | ฿ + ราคาต่อร้าน | curated (ตาม decision ข้อ 1) |

### 1.3 อัลกอริทึมการแนะนำ (5 ขั้น)
1. **Filter** — เลือกสินค้าที่ `triggers` ตรงกับ spec + **ตัด `owned_items` ออก**
2. **Prioritize** — จัดลำดับ tier: `must` → `recommended` → `optional`; ภายใน tier เรียงตามความคุ้มค่า (ref_price ต่อประโยชน์) และจัดกลุ่มตาม category
3. **Budget fitting (greedy)** — ใส่ `must` ก่อนจนครบ → ตามด้วย `recommended` → `optional` พร้อมนับยอดสะสมเทียบ `budget`
4. **Cascading bundle** — สำหรับสินค้าที่เลือก ดึง `pairs_with` มาโชว์เป็น Smart Bundle — **จำกัดความลึก 1 ชั้นใน MVP** (กัน dependency ระเบิด); "เป็นทอดๆ" เต็มรูปเป็น phase 2
5. **Quantity scaling** — ของสิ้นเปลืองคูณตาม `qty_scales_by` (เช่น ผงซักฟอก × occupants)

### 1.4 Logic ตอน "เกินงบ" — (ป้อนตรงเข้า wireframe หน้าเกินงบ)
กติกาการตัดสินใจ เรียงตามลำดับ:

- **ถ้า Σ(must) ≤ budget** แต่รวม recommended/optional แล้วเกิน
  → **ระบบเสนอย้าย `optional` ที่ priority ต่ำสุดไป "Restock รอบหน้า" อัตโนมัติ** จนยอดกลับเข้างบ
  → โชว์ชัดว่า *ตัดชิ้นไหน* และ *ยอดใหม่เท่าไร*

- **ถ้า Σ(must) > budget** (ของจำเป็นล้วน ๆ ก็ยังเกิน)
  → **ห้ามตัด must เงียบ ๆ** — บอกตามตรงว่างบตึง แล้วเสนอ 2 ทาง:
    1. SKU ทางเลือกที่ถูกกว่าในชิ้นเดิม (ถ้ามีใน catalog)
    2. แบ่งซื้อข้ามเดือน (เลื่อน must บางส่วนไปช่วงเวลาถัดไปในปฏิทิน)

- **หลักการเหนือกฎทั้งหมด:** ผู้ใช้เป็นคนกดยืนยันการตัดเสมอ (ระบบ *เสนอ* ไม่ *บังคับ*) — รักษา sense of control ตาม journey map จุด make-or-break

### 1.5 ตัดสิน Open Item ที่ค้าง
- **Smart Bundle = module ไม่ใช่หน้าเดี่ยว** — ฝังใน Product Detail และโผล่แบบ inline ในหน้า Recommendations ไม่ต้องมี route แยก (ง่ายกว่า + ตรง sitemap เดิม)

---

## Part 2 — Success Metrics

ผูกกับสมมติฐานหลัก: *"ตัวช่วยจัดของตามงบมีคุณค่าจริงไหม"* — และจำไว้ว่า **ความสำเร็จของแอป = ผู้ใช้ตัดสินใจได้แล้วออกไปซื้อจริง** (ไม่ใช่ค้างอยู่ในแอปนาน ๆ)

### 2.1 North Star
> **% ของ session ที่จบด้วยการเซฟหรือส่งออกแผน**
> = สัดส่วนคนที่ได้รับคุณค่าแกนกลางจริง

### 2.2 Metrics รอง (โฟกัส 5 ตัวใน MVP)
| ช่วง | Metric | ทำไมสำคัญ |
|---|---|---|
| Activation | % ที่ทำ Wizard จบ → เห็น Recommendations | วัดว่า onboarding ไม่ทำคนหลุด |
| Value moment | % ที่จัดแผน **ได้อยู่ในงบ** | คุณค่าแกน (advisor ตามงบ) เกิดจริงไหม |
| **Handoff (เป้าจริง)** | % ที่กด "ไปซื้อจริง" / export ลิสต์ | แอปเป็น advisor → นี่คือ success ที่แท้ |
| Recovery | **over-budget recovery rate** = % ที่เจอเกินงบแล้ว *ปรับแล้วยังเซฟต่อ* (ไม่ทิ้ง) | วัดตรงจุด make-or-break |
| Retention | % กลับมาทำ Restock รอบ 2 ภายใน 30 วัน | loop ระยะยาวเวิร์กไหม |

### 2.3 Guardrail (เฝ้าไม่ให้พัง)
- Drop-off rate ต่อคำถามใน Wizard (ถ้าข้อไหนคนหลุดเยอะ = คำถามแย่)
- Bounce บนหน้า Landing ก่อนเริ่ม Wizard/Explore

### 2.4 ยังไม่วัดใน MVP
- Cohort/LTV, conversion ต่อ affiliate เป็นตัวเงิน (รอ traffic จริงก่อน), NPS

---

## สรุป Open Items ที่เอกสารนี้ปิด
- ✅ Recommendation logic (rule-based 5 ขั้น + over-budget rules)
- ✅ Success metrics (North Star + 5 รอง + guardrail)
- ✅ Smart Bundle = module (ไม่ใช่หน้าเดี่ยว)

**ยังค้าง** (ไม่บล็อกการ wireframe หน้าเกินงบ): empty/error/legal pages, ภาษา, web-push fallback
