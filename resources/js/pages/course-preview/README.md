# Course Preview Feature

Halaman preview course yang dinamis untuk menampilkan konten course berdasarkan courseID.

## Fitur Utama

### 1. **Dynamic Course Loading**
- Mengambil data course berdasarkan courseID dari URL parameter
- Loading state dengan skeleton/spinner
- Error handling dengan pesan yang informatif

### 2. **Interactive Sidebar Navigation**
- Menampilkan thumbnail dan nama course
- Daftar semua content course dengan icon berdasarkan type
- Active state untuk content yang sedang ditampilkan
- Navigasi back to dashboard

### 3. **Dynamic Content Rendering**
- **Text Content**: Menggunakan komponen `ContentText.vue`
- **Video Content**: Menggunakan komponen `ContentVideo.vue`
- Auto-render HTML content dari API
- Support untuk attachment/lampiran

### 4. **Progress Tracking**
- Mark as completed functionality
- Auto-advance ke content berikutnya
- Progress indication
- Course completion detection

## Struktur File

```
course-preview/
├── index.vue           # Main preview page
├── ContentText.vue     # Text content component
└── ContentVideo.vue    # Video content component
```

## Cara Menggunakan

### Navigasi ke Preview
Dari halaman course detail, klik tombol **"Preview Course"** atau akses langsung:
```
/course/{courseId}/preview
```

### API Requirements
Endpoint `getCourseById(courseId)` harus mengembalikan struktur:
```javascript
{
  success: true,
  data: {
    id: number,
    name: string,
    thumbnail: string,
    contents: {
      data: [
        {
          id: number,
          title: string,
          type: 'text' | 'video',
          body: string,        // HTML content
          video_url: string,   // For video type
          attachment: string   // Optional attachment URL
        }
      ]
    }
  }
}
```

## Komponen Detail

### ContentText.vue
```vue
<ContentText :content="currentContent" />
```
**Props:**
- `content`: Object dengan properties `title`, `body`, `attachment`

**Features:**
- Styled prose content
- Download attachment button
- Responsive design

### ContentVideo.vue
```vue
<ContentVideo :content="currentContent" />
```
**Props:**
- `content`: Object dengan properties `title`, `video_url`, `body`, `attachment`

**Features:**
- Auto-convert YouTube/Vimeo URLs to embeddable format
- Fallback UI untuk video yang tidak tersedia
- Support untuk video description
- Download resource button

## Customization

### Styling
Kedua komponen menggunakan scoped CSS dengan class `.prose` untuk styling content:
- Typography yang konsisten
- Responsive images
- Table styling
- Code highlighting
- Custom blockquotes

### Icon Mapping
```javascript
const getContentIcon = (type) => {
  switch (type) {
    case 'video': return '/assets/images/icons/video-play-white.svg';
    case 'text': return '/assets/images/icons/note-white.svg';
    case 'finished': return '/assets/images/icons/tick-circle-white.svg';
    default: return '/assets/images/icons/note-white.svg';
  }
};
```

## State Management
- `course`: Data course dari API
- `contents`: Array of content dari course
- `currentContentIndex`: Index content yang sedang aktif
- `loading`: Loading state
- `error`: Error state

## Navigation Logic
- Auto-select content pertama saat load
- Click sidebar item untuk ganti content
- Mark as completed untuk advance ke content berikutnya
- Deteksi course completion
