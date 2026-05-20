"use client";

import { useState } from "react";

export default function Comments() {
  const [comments, setComments] = useState([
    { id: 1, name: "Ali Hassan", date: "2 days ago", content: "Great insights on the regional security trends. Looking forward to the full report." },
    { id: 2, name: "Maria Tekle", date: "1 week ago", content: "The educational data for Ethiopia is very helpful for our research project." },
  ]);

  const [newComment, setNewComment] = useState("");

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newComment.trim()) return;
    
    // Simulate auto-moderation success
    const comment = {
      id: Date.now(),
      name: "Public User",
      date: "Just now",
      content: newComment,
    };
    
    setComments([comment, ...comments]);
    setNewComment("");
  };

  return (
    <div className="max-w-4xl mx-auto py-16 border-t border-slate-200 mt-20">
      <h3 className="font-outfit text-2xl font-bold mb-10 flex items-center gap-3">
        Community Discussion
        <span className="text-xs bg-slate-100 text-slate-500 px-3 py-1 rounded-full font-bold">{comments.length}</span>
      </h3>

      <form onSubmit={handleSubmit} className="mb-16">
        <textarea 
          rows={3} 
          className="w-full glass p-6 rounded-[2rem] border-white/60 bg-white/50 focus:ring-2 focus:ring-secondary/50 focus:border-secondary/50 focus:outline-none transition-all font-medium text-sm mb-4"
          placeholder="Join the discussion... (Comments are moderated for safety)"
          value={newComment}
          onChange={(e) => setNewComment(e.target.value)}
        ></textarea>
        <button type="submit" className="btn-primary py-3 px-8 text-sm flex items-center gap-2">
           Post Comment
           <span className="text-[10px] bg-white/20 px-2 py-0.5 rounded text-white italic">Auto-Mod Active</span>
        </button>
      </form>

      <div className="space-y-10">
        {comments.map((comment) => (
          <div key={comment.id} className="flex gap-6 group">
            <div className="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-lg font-bold text-slate-300 shadow-inner group-hover:bg-[#1F0404]/5 group-hover:text-primary transition-colors">
              {comment.name[0]}
            </div>
            <div className="flex-grow">
              <div className="flex items-center gap-3 mb-2">
                <span className="font-bold text-slate-900">{comment.name}</span>
                <span className="text-[10px] uppercase tracking-widest font-black text-slate-300 italic">{comment.date}</span>
              </div>
              <p className="text-slate-600 text-sm leading-relaxed">{comment.content}</p>
              <div className="mt-4 flex gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                 <button className="text-[10px] font-bold uppercase tracking-widest text-slate-400 hover:text-primary">Reply</button>
                 <button className="text-[10px] font-bold uppercase tracking-widest text-slate-400 hover:text-rose-500">Report</button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
