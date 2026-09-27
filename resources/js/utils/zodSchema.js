import { z } from "zod";

export const signUpSchema = z.object({
    name: z.string().min(5),
    email: z.string().email(),
    password: z.string().min(5),
});

export const signInSchema = signUpSchema.omit({ name: true });

export const createCourseSchema = z.object({
    name: z.string().min(2),
    categoryId: z.number().min(1, { message: "Please select category" }),
    tagline: z.string().min(5),
    description: z.string().min(10),
    thumbnail: z.any().refine((file) => file?.name, {
        message: "Thumbnail is required",
    }),
});

export const updateCourseSchema = createCourseSchema.partial({
    thumbnail: true,
});

export const AnnouncementInfoSchema = z.object({
    title: z.string().trim().min(1, { message: "Judul wajib diisi" }).max(150, { message: "Judul maksimal 150 karakter" }),
    no_surat: z.string().min(5),
    dokumen: z.any().optional(),
    type: z.enum(["text", "pdf"], {
      errorMap: (issue, ctx) => {
        console.log('issue', issue);
        if (issue.code === "invalid_enum_value") {
          return { message: "Tipe file wajib dipilih" };
        }
        return { message: "Required content type" };
      }
    }),
    tgl_berlaku: z.string(),// success: true,
    submenu_id: z.number().min(1, {
        message: "Please select submenu",
    }),
    created_at: z.string().optional(),
}).superRefine((val, ctx) => {
    if (val.tgl_berlaku && !Date.parse(val.tgl_berlaku)) {
        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: "Invalid date format",
            path: ["tgl_berlaku"],
        });
    }
    
    if(val.dokumen && !(val.dokumen instanceof File)) {
        ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: "Dokumen must be a valid file",
            path: ["dokumen"],
        });
    }
});

export const mutateContentSchema = z
    .object({
        title: z.string().trim().min(1, { message: "Judul wajib diisi" }).max(150, { message: "Judul maksimal 150 karakter" }),
        type: z.enum(["text", "video", "pdf", "form", "google_form", "quiz"], {
            errorMap: () => ({ message: "Required content type" }),
        }),
        youtubeId: z.string().optional(),
        formUrl: z.string().optional(),
        text: z.string().optional(),
        pdf: z.any().optional(),
        quiz: z.any().optional(),
        quizId: z.string().optional(),
        course_id: z.number().or(z.string()).optional(), // Add course_id
        order: z.number().optional(), // Add order
    })
    .superRefine((val, ctx) => {
        const parseVideoId = z.string().min(4).safeParse(val.youtubeId);
        const parseFormUrl = z.string().min(4).safeParse(val.formUrl);
        const parseText = z.string().min(4).safeParse(val.text);
        const parsePdf = z.any().safeParse(val.pdf);
        if (val.type === 'quiz' && !/^[1-9]\d*$/.test(val.quizId || '')) {
            ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Pilih quiz untuk course ini.', path: ['quizId'] });
        }

        if (val.type === "video") {
            if (!parseVideoId.success) {
                console.log("masukk yeee video");

                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: "Youtube ID is required",
                    path: ["youtubeId"],
                });
            }
        }

        if (val.type === "form" || val.type === "google_form") {
            if (!parseFormUrl.success) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: "URL / Embed Google Form wajib diisi",
                    path: ["formUrl"],
                });
            }
        }   

        if (val.type === "text") {
            if (!parseText.success) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    message: "Content Text is required",
                    path: ["text"],
                });
            }
        }
    });
